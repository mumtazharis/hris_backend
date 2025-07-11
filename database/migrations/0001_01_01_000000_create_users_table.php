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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->nullable();
            $table->foreign('company_id')->references('company_id')->on('companies');
            $table->string('user_photo')->nullable();
            $table->string('full_name')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('phone')->unique()->nullable();
            $table->string('password')->nullable();
            $table->string('google_id')->unique()->nullable();
            $table->enum('role', ['admin', 'employee'])->default('employee');
            $table->boolean('is_profile_complete')->default(false);
            $table->enum('auth_provider', ['google', 'local'])->default('local');
            $table->string('reset_token')->nullable();
            $table->timestamp('reset_token_expire')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
