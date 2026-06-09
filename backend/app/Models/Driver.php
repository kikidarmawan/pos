<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'no_ktp',
        'name',
        'address',
        'gender',
        'birth_place',
        'birth_date',
        'phone',
        'ktp_photo',
        'photo',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];
}
