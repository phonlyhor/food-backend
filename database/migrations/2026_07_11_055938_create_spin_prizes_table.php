<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('spin_prizes', function (Blueprint $table) {
            $table->id();
             $table->string('name');               // Prize Name

    $table->enum('prize_type', [
        'coupon',
        'product',
        'point',
        'none'
    ]);

    $table->string('prize_value')->nullable();

    $table->decimal('chance', 5, 2);

    $table->integer('quantity')->default(0);

    $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spin_prizes');
    }
};
