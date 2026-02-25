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

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class)->latest();
    }

    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription() !== null;
    }

    /**
     * Ambil data toko (single store)
     */
    public static function current(): ?self
    {
        return static::first();
    }
}
