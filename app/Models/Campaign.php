<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image_url',
        'target_amount',
        'collected_amount', // <--- Kolom PENTING yang akan kita update otomatis
        'organizer',
        'is_active',
    ];

    // Relasi: Satu Campaign punya banyak Donasi
    public function donations()
    {
        return $this->hasMany(Donation::class);
    }
}