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
        Schema::create('letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('letter_format_id')->constrained('letter_formats');
            $table->foreignId('user_id')->constrained('users');
            $table->string('name'); // perlu ditanyakan lebih lanjut
            $table->text('content'); // berisi kode json/ sejenisnya yang menyimpan data inputan yang akan ditampilkan pada halaman bukan sebagai satu dokumen 
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letters');
    }
};
