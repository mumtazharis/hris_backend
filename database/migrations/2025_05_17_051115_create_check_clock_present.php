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
        Schema::create('present_detail_cc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ck_id')->constrained('check_clocks');
            $table->string('check_clock_type'); // berisi in, out, break_start, break_end, sick, permit, leave(cuti)
            $table->time('check_clock_time')->nullable();
            $table->string('latitude')->nullable(); // berisi koordinat lokasi absensi seperti gps, dll
            $table->string('longitude')->nullable(); // berisi koordinat lokasi absensi seperti gps, dll
            $table->string('evidence')->nullable(); // berisi bukti absensi seperti foto, dll
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('present_detail_cc');
    }
};
