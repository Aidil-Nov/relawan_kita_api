<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('ticket_id')->unique(); // ID Tiket (misal: RPT-8821)
            $table->string('category'); // Banjir, Longsor, dll
            $table->string('urgency'); // Rendah, Sedang, Tinggi
            $table->text('description'); // Kronologi kejadian

            // Lokasi
            $table->string('location_address'); // Alamat teks
            $table->double('latitude')->nullable(); // Koordinat GPS
            $table->double('longitude')->nullable(); // Koordinat GPS

            $table->string('photo_url'); // Foto Bukti
            $table->string('status')->default('pending'); // pending, verified, rejected, completed
            $table->text('admin_note')->nullable(); // Catatan admin jika ditolak/selesai
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
