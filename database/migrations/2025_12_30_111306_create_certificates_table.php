<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Terhubung ke user
            $table->string('title');            // Nama Sertifikat (ex: Basic Life Support)
            $table->string('issuer');           // Penerbit (ex: PMI)
            $table->string('certificate_code'); // Nomor Sertifikat (ex: CERT-001)
            $table->string('file_url')->nullable(); // Link PDF/Gambar sertifikat
            $table->date('issued_date')->nullable(); // Tanggal terbit
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
