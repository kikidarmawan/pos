<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    /**
     * Daftar paket langganan (aktif saja)
     */
    public function index()
    {
        $packages = Package::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return response()->json($packages);
    }
}
