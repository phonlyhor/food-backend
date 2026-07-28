<?php
require 'vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

$token = config('services.bakong.token');
$apiUrl = env('BAKONG_API_URL', 'https://api-bakong.nbc.gov.kh');

if (!$token) {
    echo "No token configured.\n";
    exit;
}

$response = Http::withoutVerifying()->withHeaders([
    'Authorization' => 'Bearer ' . $token,
    'Content-Type' => 'application/json'
])->post($apiUrl . '/v1/generate_qr', [
    'qrType' => 'DYNAMIC',
    'merchantId' => 'LIHOR Phon',
    'accountId' => config('services.bakong.account_id'),
    'amount' => 5.00,
    'currency' => 'USD',
    'billNumber' => 'TEST-1234'
]);

echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n";
