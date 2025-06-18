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
        Schema::create('overtime', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id');
            $table->foreign('employee_id')->references('employee_id')->on('employees')->onDelete('cascade');
            $table->foreignId('overtime_setting_id')->constrained('overtime_settings');
            $table->date('date');
            $table->time('start_hour');
            $table->time('end_hour');
            $table->integer('payroll')->nullable();
            $table->string('evidence')->nullable();
            $table->enum('status', ['Approved', 'Pending', 'Rejected'])->default('Pending');
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime');
    }
};
