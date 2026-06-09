<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'unit_id',
        'quantity',
        'price',
        'subtotal',
        'is_bonus',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'is_bonus' => 'boolean',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function deliveryItems()
    {
        return $this->hasMany(DeliveryItem::class);
    }
}
