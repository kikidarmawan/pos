<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Routes yang boleh diakses tanpa langganan aktif.
     */
    protected array $except = [
        'api/store',           // GET store (untuk baca data + subscription)
        'api/subscription*',   // subscription current, create, index
        'api/packages',        // list paket
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->inExceptArray($request)) {
            return $next($request);
        }

        $store = Store::current();
        if (!$store) {
            return $next($request);
        }

        if ($store->hasActiveSubscription()) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Langganan telah berakhir. Silakan perpanjang untuk melanjutkan menggunakan aplikasi.',
            'code' => 'subscription_expired',
        ], 403);
    }

    protected function inExceptArray(Request $request): bool
    {
        $path = $request->path();
        foreach ($this->except as $except) {
            $pattern = str_replace('*', '', $except);
            if (str_starts_with($path, $pattern) || $path === $except) {
                return true;
            }
        }
        return false;
    }
}
