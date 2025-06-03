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
        Schema::create('absent_detail_cc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ck_id')->constrained('check_clocks')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('evidence');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absent_detail_cc');
    }
};
