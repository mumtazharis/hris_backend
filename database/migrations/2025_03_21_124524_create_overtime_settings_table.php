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
            $table->string('company_id')->nullable();
            $table->foreign('company_id')->references('company_id')->on('companies');
            $table->string('name');
            // $table->string('formula_id')->constraint('overtime_formula');
            $table->string('type');
            $table->string('category');
            $table->string('working_days')->nullable();
            // $table->string('calculation');
            // $table->string('rate');
            $table->timestamps();
            $table->softDeletes();
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
