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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
                $table->string('title');
    $table->text('description')->nullable();

    $table->string('image')->nullable();
    $table->dateTime('start_date');
    $table->dateTime('end_date');
    $table->string('button_text')->nullable();
    $table->string('button_link')->nullable();
    $table->boolean('is_active')->default(true);
    $table->integer('priority')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
