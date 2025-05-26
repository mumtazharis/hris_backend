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
        Schema::create('deleted_employee_log', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users'); // user that delete the data
            $table->string('deleted_employee_name'); // id of deleted employee
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deleted_employee_log');
    }
};  