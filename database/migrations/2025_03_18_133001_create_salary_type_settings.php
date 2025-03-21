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
        Schema::create('salary_type_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('peroid'); // bisa fixed (secara bulan, minggu, hari) atau nggan
            $table->string('duration_period')->nullable();
            $table->string('start_period_month')->nullable();
            $table->string('start_period_week')->nullable();
            $table->string('start_period_days')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_type_settings');
    }
};
