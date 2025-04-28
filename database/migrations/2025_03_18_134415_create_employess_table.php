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
        Schema::create('employess', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('ck_setting_id')->constrained('check_clock_settings');
            $table->string('nik');
            $table->string('first_name');
            $table->string('last_name');
            $table->foreignId('position_id')->constrained('positions');
            $table->foreignId('department_id')->constrained('departments');
            $table->string('address');
            $table->string('contact');
            $table->string('birth_place');
            $table->date('birth_date');
            $table->string('religion');
            $table->string('marital_status');
            $table->string('citizenship');
            $table->char('gender');
            $table->string('blood_type');
            $table->date('join_date');
            $table->date('resign_date');
            $table->string('employee_photo');
            $table->string('employee_status');
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
