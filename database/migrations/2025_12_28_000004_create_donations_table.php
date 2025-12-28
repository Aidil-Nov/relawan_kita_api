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
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            // Relasi
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');

            $table->string('order_id')->unique(); // ID Unik Midtrans (misal: DON-12345)
            $table->decimal('amount', 15, 2); // Nominal donasi
            $table->string('status')->default('pending'); // pending, success, failed, expired
            $table->string('payment_method')->nullable(); // bca, bri, gopay (diisi setelah bayar)
            $table->string('snap_token')->nullable(); // Token dari Midtrans
            $table->boolean('is_anonymous')->default(false); // Fitur "Hamba Allah"
            $table->text('prayer_message')->nullable(); // Doa/Dukungan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
