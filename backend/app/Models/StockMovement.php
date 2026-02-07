<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'rack_id',
        'type',
        'reference_type',
        'reference_id',
        'quantity',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function rack()
    {
        return $this->belongsTo(Rack::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
