<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->unique()->onDelete('cascade');
            $table->string('company_id');
             $table->foreign('company_id')->references('company_id')->on('companies');
            $table->string('employee_id')->unique();
            $table->string('nik')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->foreignId('position_id')->nullable()->constrained('positions');
            // $table->foreignId('department_id')->nullable()->constrained('departments');
            // $table->string('department')->nullable();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('education', ['SD', 'SMP', 'SMA', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3', 'Other'])->nullable();
            $table->string('religion')->nullable();
            $table->enum('marital_status', ['Single', 'Married', 'Divorced', 'Widowed'])->nullable();
            $table->string('citizenship')->nullable();
            $table->enum('gender', ['Male', 'Female'])->nullable();
            $table->enum('blood_type', ['A', 'B', 'AB', 'O', 'Unknown'])->nullable();
            $table->string('salary')->nullable();
            $table->enum('contract_type', ['Permanent', 'Internship', 'Contract'])->default('Permanent')->nullable();
            $table->string('bank_code')->nullable();
            $table->foreign('bank_code')->references('code')->on('banks');
            $table->string('account_number')->nullable();

            $table->date('contract_end')->nullable();
            $table->date('join_date')->nullable();
            $table->date('exit_date')->nullable();
            $table->string('employee_photo')->nullable();
            $table->enum('employee_status', ['Active', 'Retire', 'Resign', 'Fired'])->default('Active')->nullable();
            $table->timestamps();
        });

        DB::statement("
            CREATE UNIQUE INDEX unique_nik_company_active
            ON employees (company_id, nik)
            WHERE employee_status = 'Active'
        ");

        DB::statement("
            CREATE UNIQUE INDEX unique_email_company_active
            ON employees (company_id, email)
            WHERE employee_status = 'Active'
        ");

        DB::statement("
            CREATE UNIQUE INDEX unique_phone_company_active
            ON employees (company_id, phone)
            WHERE employee_status = 'Active'
        ");

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS unique_nik_company_active");
        DB::statement("DROP INDEX IF EXISTS unique_email_company_active");
        DB::statement("DROP INDEX IF EXISTS unique_phone_company_active");
        Schema::dropIfExists('employees');
    }
};
