<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = ['name', 'address', 'phone', 'email'];

    /**
     * Ambil data toko (single store)
     */
    public static function current(): ?self
    {
        return static::first();
    }
}
