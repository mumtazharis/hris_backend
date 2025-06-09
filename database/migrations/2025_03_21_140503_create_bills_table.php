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
            $table->string('payment_id')->unique()->nullable(false); 
            $table->foreignId('user_id')->constrained('users');
            $table->integer('total_employee'); // jumlah employee jadi tagihan (termasuk yang dihapus) bulan ini
            $table->foreignId('plan_id')->constrained('billing_plans'); // id plan yang digunakan
            $table->string('plan_name'); // ← tambah kolom untuk menyimpan nama plan
            $table->decimal('amount', 12, 0); // jumlah harga yang dibayarkan
            $table->string('period', 7); // Format: mm-yyyy
            $table->date('deadline');
            $table->enum('status', ['pending', 'paid', 'overdue'])->default('pending');
            $table->dateTime('pay_at')->nullable(); // waktu pembayaran
            $table->decimal('fine', 12, 0)->nullable()->default(0); // denda
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
