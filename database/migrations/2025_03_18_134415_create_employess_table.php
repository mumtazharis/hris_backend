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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('ck_setting_id')->nullable()->constrained('check_clock_settings');
            $table->string('employee_id')->unique();
            $table->string('nik')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->foreignId('position_id')->nullable()->constrained('positions');
            $table->string('address')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('phone')->unique()->nullable();
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('religion')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('citizenship')->nullable();
            $table->char('gender')->nullable();
            $table->string('blood_type')->nullable();
            $table->string('salary')->nullable();
            $table->enum('work_status', ['permanent', 'internship', 'part-time', 'outsource'])->default('permanent')->nullable();
            $table->date('join_date')->nullable();
            $table->date('resign_date')->nullable();
            $table->string('employee_photo')->nullable();
            $table->string('employee_status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employess');
    }
};
