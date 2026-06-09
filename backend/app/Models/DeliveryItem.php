<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_id',
        'sale_detail_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function saleDetail()
    {
        return $this->belongsTo(SaleDetail::class);
    }
}
