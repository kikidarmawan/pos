<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleHold extends Model
{
    protected $fillable = [
        'user_id',
        'warehouse_id',
        'customer_id',
        'customer_name',
        'customer_phone',
        'customer_address',
        'discount',
        'tax',
        'items',
    ];

    protected $casts = [
        'items' => 'array',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
