<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Donatur
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade'); // Kampanye tujuan
            
            $table->string('order_id')->unique(); // ID Unik untuk Midtrans (Ex: DON-12345)
            $table->double('amount'); // Jumlah Donasi
            $table->string('status')->default('pending'); // pending, success, failed, expired
            $table->string('snap_token')->nullable(); // Token pembayaran dari Midtrans
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('donations');
    }
};