<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CampaignSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('campaigns')->insert([
            [
                'title' => 'Banjir Bandang Demak',
                'description' => 'Ribuan warga terdampak banjir bandang. Mereka membutuhkan makanan, selimut, dan obat-obatan segera.',
                'image_url' => 'http://172.20.10.6:8000/images/banjir.jpg', // Ganti link gambar jika perlu
                'target_amount' => 500000000,
                'collected_amount' => 125000000,
                'organizer' => 'BPBD Demak',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Gempa Cianjur Bangkit',
                'description' => 'Bantu pembangunan kembali sekolah darurat untuk anak-anak korban gempa.',
                'image_url' => 'http://172.20.10.6:8000/images/gempa.jpg',
                'target_amount' => 200000000,
                'collected_amount' => 190000000,
                'organizer' => 'Relawan Kita Pusat',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}