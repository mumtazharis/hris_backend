<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon; // Untuk bekerja dengan tanggal dan waktu

class DeletedEmployeeLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run()
    {
        // Hapus data yang ada sebelumnya untuk menghindari duplikasi saat seeding ulang
        DB::table('deleted_employee_log')->truncate();

        // Ambil bulan dan tahun saat ini
        $currentYearMonth = Carbon::now()->format('Y-m');

        // Contoh data untuk seeding
        $data = [];

        // Menambahkan 3 data untuk bulan ini
        for ($i = 0; $i < 3; $i++) {
            $data[] = [
                'user_id' => rand(1, 8), // ID user acak antara 1 dan 10
                'deleted_employee' =>  rand(1, 8), // Contoh data karyawan yang dihapus
                'created_at' => Carbon::now()->subDays(rand(0, 29))->format('Y-m-d H:i:s'), // Tanggal di bulan ini
                'updated_at' => Carbon::now()->subDays(rand(0, 29))->format('Y-m-d H:i:s'),
            ];
        }

        // Menambahkan 2 data untuk bulan lalu (agar ada yang tidak terfilter)
        for ($i = 0; $i < 2; $i++) {
            $data[] = [
                'user_id' => rand(1, 8),
                'deleted_employee' => rand(1, 8),
                'created_at' => Carbon::now()->subMonth()->subDays(rand(0, 29))->format('Y-m-d H:i:s'), // Tanggal bulan lalu
                'updated_at' => Carbon::now()->subMonth()->subDays(rand(0, 29))->format('Y-m-d H:i:s'),
            ];
        }

        // Masukkan data ke tabel
        DB::table('deleted_employee_log')->insert($data);
    }
}
