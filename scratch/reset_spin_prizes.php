<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    Schema::disableForeignKeyConstraints();
    
    // Clear old prizes and histories
    DB::table('spin_histories')->truncate();
    DB::table('spin_prizes')->truncate();
    
    $prizes = [
        ['name' => '10%', 'prize_type' => 'coupon', 'prize_value' => 'SPIN10', 'chance' => 10.00, 'quantity' => 100, 'is_active' => true],
        ['name' => '20%', 'prize_type' => 'coupon', 'prize_value' => 'SPIN20', 'chance' => 10.00, 'quantity' => 100, 'is_active' => true],
        ['name' => '30%', 'prize_type' => 'coupon', 'prize_value' => 'SPIN30', 'chance' => 10.00, 'quantity' => 100, 'is_active' => true],
        ['name' => '40%', 'prize_type' => 'coupon', 'prize_value' => 'SPIN40', 'chance' => 10.00, 'quantity' => 100, 'is_active' => true],
        ['name' => '50%', 'prize_type' => 'coupon', 'prize_value' => 'SPIN50', 'chance' => 10.00, 'quantity' => 100, 'is_active' => true],
        ['name' => '60%', 'prize_type' => 'coupon', 'prize_value' => 'SPIN60', 'chance' => 10.00, 'quantity' => 100, 'is_active' => true],
        ['name' => '70%', 'prize_type' => 'coupon', 'prize_value' => 'SPIN70', 'chance' => 10.00, 'quantity' => 100, 'is_active' => true],
        ['name' => '80%', 'prize_type' => 'coupon', 'prize_value' => 'SPIN80', 'chance' => 10.00, 'quantity' => 100, 'is_active' => true],
        ['name' => '90%', 'prize_type' => 'coupon', 'prize_value' => 'SPIN90', 'chance' => 10.00, 'quantity' => 100, 'is_active' => true],
        ['name' => '100%', 'prize_type' => 'product', 'prize_value' => '22', 'chance' => 5.00, 'quantity' => 100, 'is_active' => true],
        ['name' => 'Thanks You Try again', 'prize_type' => 'none', 'prize_value' => null, 'chance' => 5.00, 'quantity' => 9999, 'is_active' => true],
    ];

    foreach ($prizes as $prize) {
        DB::table('spin_prizes')->insert(array_merge($prize, [
            'created_at' => now(),
            'updated_at' => now()
        ]));
    }
    
    Schema::enableForeignKeyConstraints();
    echo "Spin prizes table reset successfully!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
