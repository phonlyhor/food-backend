<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SpinQuotas;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use KHQR\BakongKHQR;
use KHQR\Helpers\KHQRData;
use KHQR\Models\IndividualInfo;

class PaymentController extends Controller
{
    private function makeTag($id, $value)
    {
        return sprintf('%02d%02d%s', $id, strlen($value), $value);
    }

    private function crc16_checksum($str)
    {
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($str); $i++) {
            $x = (($crc >> 8) ^ ord($str[$i])) & 0xFF;
            $x ^= $x >> 4;
            $crc = (($crc << 8) ^ ($x << 12) ^ ($x << 5) ^ ($x << 0)) & 0xFFFF;
        }
        return sprintf('%04X', $crc);
    }

    private function generateKHQRString($accountId, $merchantName, $amount, $tranId)
    {
        $payload = '';
        $payload .= $this->makeTag(0, '01'); // Payload Format Indicator
        $payload .= $this->makeTag(1, '12'); // Dynamic QR (12)
        
        // Tag 29: Merchant Account Info (Bakong template)
        $tag29Value = '';
        $tag29Value .= $this->makeTag(0, 'bakong.nbc.gov.kh'); // GUID
        $tag29Value .= $this->makeTag(1, $accountId); // Bakong Account ID
        
        $parts = explode('@', $accountId);
        if (count($parts) > 1) {
            $tag29Value .= $this->makeTag(2, $parts[1]); // Subtag 02 (Acquiring Bank)
        }
        
        $payload .= $this->makeTag(29, $tag29Value);
        $payload .= $this->makeTag(52, '5999'); // Category: Food
        $payload .= $this->makeTag(53, '840'); // Currency: USD (840)
        $payload .= $this->makeTag(54, number_format($amount, 2, '.', ''));
        $payload .= $this->makeTag(58, 'KH'); // Country Code
        $payload .= $this->makeTag(59, substr($merchantName, 0, 25)); // Merchant Name
        $payload .= $this->makeTag(60, 'Phnom Penh'); // City
        
        // Tag 62: Additional Data (Bill ID)
        if ($tranId) {
            $tag62Value = $this->makeTag(1, $tranId);
            $payload .= $this->makeTag(62, $tag62Value);
        }
        
        // Tag 63: CRC16 Checksum
        $payload .= '6304';
        $checksum = $this->crc16_checksum($payload);
        
        return $payload . $checksum;
    }

    private function getBakongApiUrl($path)
    {
        $isProduction = env('BAKONG_PRODUCTION', false);
        $base = $isProduction 
            ? env('BAKONG_API_URL', 'https://api-bakong.nbc.gov.kh')
            : env('BAKONG_API_URL', 'https://sit-api-bakong.nbc.gov.kh');
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Initiate Payment for an Order
     */
    public function initiatePayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|in:cod,bakong',
        ]);

        $order = Order::findOrFail($request->order_id);
        $userId = auth()->id() ?? $order->user_id;

        $payment = Payment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'user_id' => $userId,
                'payment_method' => $request->payment_method,
                'amount' => $order->total_amount,
                'currency' => 'USD',
                'status' => 'pending'
            ]
        );

        if ($request->payment_method === 'cod') {
            return response()->json([
                'success' => true,
                'payment_method' => 'cod',
                'message' => 'Cash on delivery payment selected.'
            ]);
        }

        if ($request->payment_method === 'bakong') {
            $bakongAccountId = config('services.bakong.account_id', 'liihorr_food@bakong');
            $tranId = 'BAKONG-' . $order->id . '-' . time();
            $amount = $order->total_amount;

            // Use custom offline generator to avoid SDK bugs
            $qrString = $this->generateKHQRString($bakongAccountId, 'LIHOR Phon', $amount, $tranId);

            $md5 = md5($qrString);
            $qrImageUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrString);
            $qrSvg = (string) QrCode::format('svg')->size(300)->generate($qrString);

            $payment->update([
                'transaction_id' => $tranId,
                'md5' => $md5
            ]);

            return response()->json([
                'success' => true,
                'payment_method' => 'bakong',
                'transaction_id' => $tranId,
                'khqr_string' => $qrString,
                'qr_image_url' => $qrImageUrl,
                'qr_code_svg' => $qrSvg,
                'amount' => $amount,
                'md5' => $md5,
                'message' => 'Please scan this KHQR code using your Bakong app.'
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid payment method'], 400);
    }

    /**
     * Webhook/Callback from Bakong KHQR
     */
    public function ipnCallback(Request $request)
    {
        Log::info('Payment Gateway IPN webhook received', $request->all());

        $tranId = $request->input('tran_id') ?? $request->input('transaction_id') ?? $request->input('billNumber');
        $md5 = $request->input('md5');
        $status = $request->input('status') ?? $request->input('state');

        if (!$tranId && !$md5) {
            return response()->json(['success' => false, 'message' => 'Missing transaction identifier'], 400);
        }

        $payment = null;
        if ($tranId) {
            $payment = Payment::where('transaction_id', $tranId)->first();
        }
        if (!$payment && $md5) {
            $payment = Payment::where('md5', $md5)->first();
        }

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Transaction record not found'], 404);
        }

        // Only proceed if the webhook claims success. 
        if (in_array($status, ['completed', '0']) || $request->input('success') === true) {
            
            // SECURITY: Verify with the gateway rather than trusting the webhook payload
            if ($this->verifyTransactionStatus($payment)) {
                $this->processSuccessfulPayment($payment);
                return response()->json(['success' => true, 'message' => 'Payment processed successfully.']);
            }
            
            return response()->json(['success' => false, 'message' => 'Signature or status verification failed.'], 403);
        }

        // Handle failure
        $payment->update(['status' => 'failed']);
        Order::where('id', $payment->order_id)->update(['status' => 'pending']);
        return response()->json(['success' => true, 'message' => 'Payment marked as failed.']);
    }

    /**
     * Frontend polling status check
     */
    public function checkPaymentStatus(Request $request, $orderId)
    {
        $payment = Payment::where('order_id', $orderId)->first();
        
        if (!$payment) {
            return response()->json(['success' => false, 'status' => 'not_found']);
        }

        if ($payment->status === 'completed') {
            return response()->json([
                'success' => true, 
                'status' => 'completed', 
                'payment_method' => $payment->payment_method
            ]);
        }

        if ($payment->payment_method === 'bakong' && $request->query('verify') == 1) {
            if ($this->verifyTransactionStatus($payment)) {
                $this->processSuccessfulPayment($payment);
                $payment->refresh(); // Refresh model after update
            }
        }

        return response()->json([
            'success' => true,
            'status' => $payment->status,
            'payment_method' => $payment->payment_method
        ]);
    }

    /**
     * Call Bakong API to verify the true status of the transaction
     */
    private function verifyTransactionStatus(Payment $payment): bool
    {
        $bakongToken = config('services.bakong.token');
            
        $md5 = $payment->md5;
        if (!$md5 && $payment->transaction_id) {
            // Compute md5 if it's not present (useful for legacy rows)
            $bakongAccountId = config('services.bakong.account_id');
            $khqrString = $this->generateKHQRString($bakongAccountId, 'LIHOR Phon', $payment->amount, $payment->transaction_id);
            $md5 = md5($khqrString);
            $payment->update(['md5' => $md5]);
        }

        if (!$md5) {
            return false;
        }

        $apiUrl = $this->getBakongApiUrl('v1/check_transaction_by_md5');

        try {
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $bakongToken,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])->post($apiUrl, [
                    'md5' => $md5
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $responseCode = $data['responseCode'] ?? null;
                $transactionStatus = $data['data']['transactionStatus'] ?? $data['data']['status'] ?? null;
                
                if ($responseCode === 0 || $responseCode === '0' || strtolower((string)$transactionStatus) === 'completed') {
                    return true;
                }
            }
        } catch (\Exception $e) {
            Log::error('Bakong Status Check failed: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Centralized function to safely award logic upon successful payment.
     * Uses database transactions to prevent duplicate spin quotas via race conditions.
     */
    private function processSuccessfulPayment(Payment $payment)
    {
        DB::transaction(function () use ($payment) {
            // Lock row for update to prevent simultaneous IPN and Polling requests 
            // from processing the rewards twice.
            $lockedPayment = Payment::where('id', $payment->id)->lockForUpdate()->first();

            if ($lockedPayment->status === 'completed') {
                return; // Already processed by another thread
            }

            $lockedPayment->update(['status' => 'completed']);
            $order = Order::find($lockedPayment->order_id);

            if ($order && !in_array($order->status, ['completed', 'processing'])) {
                $order->update(['status' => 'processing']);

                // Award spin quotas if payment amount is >= $5.00
                if ($order->total_amount >= 5.00) {
                    $today = Carbon::today()->toDateString();
                    
                    $quota = SpinQuotas::firstOrCreate(
                        ['user_id' => $order->user_id, 'date' => $today],
                        ['spin_count' => 0]
                    );
                    
                    $quota->increment('spin_count', 2);
                }

                // ── Bắn Telegram Notification នៅទីនេះ ──────────────────────
                $this->sendTelegramNotification($order, $order->total_amount);
            }
        });
    }

    /**
     * Telegram Notification សម្រាប់បញ្ជូនសារនៅពេលមានអ្នកទិញជោគជ័យ
     */
    private function sendTelegramNotification(Order $order, float $amount): void
    {
        // ប្រើ env() យកពី .env file (ត្រូវប្រាកដថាបានដាក់ TELEGRAM_BOT_TOKEN និង TELEGRAM_CHAT_ID ក្នុង .env)
        $token  = config('services.telegram.bot_token') ?? env('TELEGRAM_BOT_TOKEN');
        $chatId = config('services.telegram.chat_id') ?? env('TELEGRAM_CHAT_ID');

        if (!$token || !$chatId) return;

        // ទាញទិន្នន័យពាក់ព័ន្ធមកទាំងអស់ដើម្បីកុំឱ្យវា error ពេលហៅប្រើ
        $order->loadMissing(['user', 'items.product', 'items.variant']);
        $user = $order->user;

        // រៀបចំបញ្ជីទំនិញ
        $itemLines = '';
        if ($order->items) {
            foreach ($order->items as $i => $item) {
                $productName = $item->product->name  ?? 'N/A';
                $sizeName    = $item->variant->size  ?? 'N/A';
                $qty         = $item->qty;
                $price       = number_format($item->price, 2);
                $itemLines  .= "  " . ($i + 1) . ". {$productName} ({$sizeName}) x{$qty} = \${$price}\n";
            }
        } else {
            $itemLines = "  គ្មានទិន្នន័យទំនិញ (No items found)\n";
        }

        // សរសេរសារជាទម្រង់ Markdown
        $message = "🛒 *ការបញ្ជាទិញថ្មីត្រូវបានបង់ប្រាក់ជោគជ័យ!*\n"
            . "━━━━━━━━━━━━━━━━━━━━━━\n\n"
            . "👤 *ព័ត៌មានអតិថិជន (Customer Info)*\n"
            . "• ឈ្មោះ:  `" . ($user->name ?? 'N/A') . "`\n"
            . "• លេខទូរស័ព្ទ: `" . ($user->phone ?? 'N/A') . "`\n\n"
            . "📦 *ព័ត៌មានការបញ្ជាទិញ (Order Info)*\n"
            . "• លេខកូដវិក្កយបត្រ:  `#{$order->id}`\n" // ប្រសិនបើអ្នកមាន order_number អាចប្តូរដាក់ $order->order_number បាន
            . "• ស្ថានភាព:    `បានបង់ប្រាក់ (Paid) ✅`\n"
            . "• សរុបទឹកប្រាក់:     `\$" . number_format($amount, 2) . "`\n\n"
            . "🛍️ *បញ្ជីទំនិញ (Items)*\n"
            . $itemLines
            . "\n🕐 `" . now()->format('Y-m-d H:i:s') . "`";

        try {
            Http::withoutVerifying()
                ->timeout(5)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id'    => $chatId,
                    'text'       => $message,
                    'parse_mode' => 'Markdown',
                ]);
        } catch (\Throwable $e) {
            Log::warning('បរាជ័យក្នុងការផ្ញើសារ Telegram: ' . $e->getMessage());
        }
    }
}