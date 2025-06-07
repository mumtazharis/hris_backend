<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\table;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('overtime_formula', function (Blueprint $table) {
            $table->id();
            $table->foreignId('setting_id')->constrained('overtime_settings');
            $table->integer('hour_start')->nullable();
            $table->integer('hour_end')->nullable();
            $table->integer('interval_hours')->nullable();
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
