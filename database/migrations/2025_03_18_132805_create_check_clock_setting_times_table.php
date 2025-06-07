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
        Schema::create('check_clock_setting_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ck_setting_id')->constrained('check_clock_settings');
            $table->string('day');
            $table->time('min_clock_in')->nullable();
            $table->time('clock_in')->nullable();
            $table->time('max_clock_in')->nullable();
            $table->time('clock_out')->nullable();
            $table->time('max_clock_out')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_clock_setting_times');
    }
};
