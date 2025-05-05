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
        Schema::create('check_clocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->string('check_clock_type'); // berisi in, out, break_start, break_end, sick, permit, leave(cuti)
            $table->date('check_clock_date');
            $table->time('check_clock_time');
            $table->string('latitude')->nullable(); // berisi koordinat lokasi absensi seperti gps, dll
            $table->string('longitude')->nullable(); // berisi koordinat lokasi absensi seperti gps, dll
            $table->string('evidence')->nullable(); // berisi bukti absensi seperti foto, dll
            $table->enum('status', ['aprrove', 'pending', 'reject'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_clocks');
    }
};
