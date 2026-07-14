<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SpinPrice;
use App\Models\SpinHistory;
use App\Models\SpinQuotas;
use App\Models\Coupon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SpinController extends Controller
{
    public function prizes()
    {
        return response()->json(
            SpinPrice::where('is_active', true)
                ->where('quantity', '>', 0)
                ->get()
        );
    }

    public function spin()
    {
        $userId = Auth::id();
        $today = Carbon::today()->toDateString();

        // Get or initialize daily quota (default 1 free spin per day)
        $quota = SpinQuotas::where('user_id', $userId)
            ->where('date', $today)
            ->first();

        if (!$quota) {
            $quota = SpinQuotas::create([
                'user_id' => $userId,
                'spin_count' => 1,
                'date' => $today
            ]);
        }

        if ($quota->spin_count <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have any spins left for today. Buy items of $5 or more to earn another spin!'
            ], 400);
        }

        return DB::transaction(function () use ($userId, $today, $quota) {
            $prizes = SpinPrice::where('is_active', true)
                ->where('quantity', '>', 0)
                ->lockForUpdate()
                ->get();

            $wonPrize = $this->drawPrize($prizes);

            // If no prize drawn, fallback to the 'none' type prize if available to prevent SQL constraints crash
            if (!$wonPrize) {
                $wonPrize = SpinPrice::where('prize_type', 'none')
                    ->where('is_active', true)
                    ->first();
            }

            if (!$wonPrize) {
                $wonPrize = $prizes->first();
            }

            if ($wonPrize && $wonPrize->prize_type !== 'none') {
                $wonPrize->decrement('quantity');
            }

            // Auto-create/activate the coupon code in the coupons table if won
            if ($wonPrize && $wonPrize->prize_type === 'coupon' && $wonPrize->prize_value) {
                $exists = \App\Models\Coupon::where('code', $wonPrize->prize_value)->exists();
                if (!$exists) {
                    $isPercentage = strpos($wonPrize->name, '%') !== false;
                    $discountValue = (float) filter_var($wonPrize->prize_value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    if ($discountValue <= 0) {
                        $discountValue = 5.00;
                    }

                    \App\Models\Coupon::create([
                        'name' => $wonPrize->name,
                        'code' => $wonPrize->prize_value,
                        'type' => $isPercentage ? 'percentage' : 'fixed',
                        'discount_value' => ($isPercentage && $discountValue > 100) ? 10.00 : $discountValue,
                        'minimum_order' => 0.00,
                        'usage_limit' => 50, // Let multiple users use the won code or high limit
                        'used_count' => 0,
                        'start_date' => Carbon::today()->toDateString(),
                        'end_date' => Carbon::today()->addDays(30)->toDateString(),
                        'status' => true
                    ]);
                }
            }

            $history = SpinHistory::create([
                'user_id' => $userId,
                'spin_prize_id' => $wonPrize->id,
            ]);

            // Decrement the user's spin quota via query builder (to avoid primary key id issue)
            SpinQuotas::where('user_id', $userId)
                ->where('date', $today)
                ->decrement('spin_count');

            return response()->json([
                'success' => true,
                'message' => $wonPrize && $wonPrize->prize_type !== 'none'
                    ? 'Congratulations! You won a prize.'
                    : 'Better luck next time!',
                'won' => $wonPrize && $wonPrize->prize_type !== 'none',
                'prize' => $wonPrize,
                'history_id' => $history->id,
            ]);
        });
    }

    private function drawPrize($prizes)
    {
        if ($prizes->isEmpty()) {
            return null;
        }

        $totalWeight = $prizes->sum('chance');
        if ($totalWeight <= 0) {
            return null;
        }

        // Generate a random float value between 0 and $totalWeight
        $roll = (mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax()) * $totalWeight;

        $currentWeight = 0;
        foreach ($prizes as $prize) {
            $currentWeight += (float) $prize->chance;
            if ($roll <= $currentWeight) {
                return $prize;
            }
        }

        return $prizes->first();
    }

    public function history()
    {
        return response()->json(
            SpinHistory::with('spinPrize')
                ->where('user_id', auth()->id())
                ->latest()
                ->get()
        );
    }

    public function status()
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json([
                'can_spin' => false,
                'message' => 'Please log in to spin the wheel.'
            ]);
        }

        $today = Carbon::today()->toDateString();

        // Get or initialize daily quota (default 1 free spin per day)
        $quota = SpinQuotas::where('user_id', $userId)
            ->where('date', $today)
            ->first();

        if (!$quota) {
            $quota = SpinQuotas::create([
                'user_id' => $userId,
                'spin_count' => 1,
                'date' => $today
            ]);
        }

        if ($quota->spin_count <= 0) {
            return response()->json([
                'can_spin' => false,
                'spins_left' => 0,
                'message' => 'No spins left today. Complete an order of $5.00 or more to earn another spin!'
            ]);
        }

        return response()->json([
            'can_spin' => true,
            'spins_left' => $quota->spin_count,
            'message' => "You have {$quota->spin_count} spin(s) available!"
        ]);
    }
}