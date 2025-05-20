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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('payment_id')->unique()->nullable(false); // id unik tiap pembayaran
            $table->foreignId('user_id')->constrained('users');
            $table->integer('total_employee'); // jumlah employee jadi tagihan (termasuk employee yang dihapus) bulan ini
            $table->decimal('amount', 12, 0); // jumlah harga yang dibayarkan (disini di set max 12 digit)
            $table->date('period'); // periode pembayaran per bulan/tahun (di db tetep kesimpen format yyyy:MM:dd)
            $table->date('deadline');
            $table->enum('status', ['pending', 'paid', 'overdue', 'failed'])->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
