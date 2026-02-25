<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'store_id',
        'package_id',
        'status',
        'started_at',
        'expires_at',
        'midtrans_order_id',
        'midtrans_payment_type',
        'midtrans_transaction_status',
        'paid_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->expires_at
            && $this->expires_at->isFuture();
    }
}
