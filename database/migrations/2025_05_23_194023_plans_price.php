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
        Schema::create('plans_price', function (Blueprint $table) {
            $table->foreignId('plan_id')->constrained('billing_plans'); // user that delete the data
            $table->integer('employee_min');
            $table->integer('employee_max')->nullable();
            $table->decimal('price', 12, 0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_plans');
    }
};
