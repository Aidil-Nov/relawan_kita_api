<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    // TAMBAHKAN BAGIAN INI:
    protected $fillable = [
        'user_id',
        'ticket_id',
        'category',
        'urgency',
        'description',
        'location_address',
        'latitude',
        'longitude',
        'photo_url',
        'status',
        'admin_note'
    ];
}