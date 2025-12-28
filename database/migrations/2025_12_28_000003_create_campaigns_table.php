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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Judul, misal: "Banjir Bandang Demak"
            $table->text('description'); // Cerita penggalangan
            $table->string('image_url')->nullable(); // URL Foto Banner
            $table->decimal('target_amount', 15, 2); // Target: 500.000.000
            $table->decimal('collected_amount', 15, 2)->default(0); // Terkumpul saat ini
            $table->string('organizer'); // Penyelenggara, misal: "BPBD"
            $table->boolean('is_active')->default(true); // Masih buka donasi atau tutup
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
