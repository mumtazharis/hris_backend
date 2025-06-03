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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->nullable();
            // $table->foreign('bank_code')->references('code')->on('banks');
            $table->foreign('employee_id')->references('employee_id')->on('employees')->onDelete('cascade');
            $table->string("document_name");
            $table->string("document_type");
            $table->date("issue_date");
            $table->date("expiry_date");
            $table->string("document");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
