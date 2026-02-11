<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = [
        'name', 'address', 'phone', 'email',
        'paper_width', 'font_size', 'show_store_header', 'default_printer_name',
    ];

    protected $casts = [
        'show_store_header' => 'boolean',
    ];

    /**
     * Ambil data toko (single store)
     */
    public static function current(): ?self
    {
        return static::first();
    }
}
