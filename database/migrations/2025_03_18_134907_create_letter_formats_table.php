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
        Schema::create('letter_formats', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('content'); // berisi kode untuk peletakkan input pada template surat, tidak hanya peletakkan input tapi format dalam encoding tertentu
            $table->integer('status'); // perlu ditanya lebih lanjut
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_formats');
    }
};
