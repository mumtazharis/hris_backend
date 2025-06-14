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
        Schema::create('check_clocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('submitter_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('ck_setting_id')->nullable()->constrained('check_clock_settings');
            $table->string('position');
            $table->date('check_clock_date');
            $table->enum('status', ['Present', 'Sick Leave', 'Annual Leave', 'Absent'])->default('Present');
            $table->enum('status_approval', ['Approved', 'Pending', 'Rejected'])->default('Pending');
            $table->text('reject_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_clocks');
    }
};
