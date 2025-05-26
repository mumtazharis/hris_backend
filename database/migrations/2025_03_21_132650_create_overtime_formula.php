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
        Schema::create('overtime_formula', function (Blueprint $table) {
            $table->foreignId('setting_id')->constrained('overtime_settings');
            $table->integer('hour_start');
            $table->integer('hour_end')->nullable();
            $table->string('formula');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_formula');
    }
};
