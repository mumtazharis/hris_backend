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
        Schema::create('overtime_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // $table->string('formula_id')->constraint('overtime_formula');
            $table->string('type');
            $table->string('category');
            $table->string('working_days');
            $table->string('calculation');
            $table->string('rate');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_settings');
    }
};
