<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'unit_id',
        'conversion_factor',
        'selling_price',
        'barcode',
        'is_default',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:3',
        'selling_price' => 'decimal:2',
        'is_default' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Convert quantity dari unit ini ke base unit
     * Contoh: 2 Roll * 100 (conversion_factor) = 200 Meter
     */
    public function toBaseUnit($quantity)
    {
        return $quantity * $this->conversion_factor;
    }

    /**
     * Convert quantity dari base unit ke unit ini
     * Contoh: 200 Meter / 100 (conversion_factor) = 2 Roll
     */
    public function fromBaseUnit($quantity)
    {
        return $quantity / $this->conversion_factor;
    }
}
