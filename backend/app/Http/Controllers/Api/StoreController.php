<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    /**
     * Ambil data identitas toko (single store)
     */
    public function show()
    {
        $store = Store::current();
        if (!$store) {
            return response()->json([
                'name' => 'Toko Saya',
                'address' => null,
                'phone' => null,
                'email' => null,
            ]);
        }
        return response()->json($store);
    }

    /**
     * Update identitas toko
     */
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
        ]);

        $store = Store::current();
        if (!$store) {
            $store = Store::create($request->only(['name', 'address', 'phone', 'email']));
            return response()->json([
                'message' => 'Identitas toko berhasil disimpan',
                'store' => $store,
            ], 201);
        }

        $store->update($request->only(['name', 'address', 'phone', 'email']));

        return response()->json([
            'message' => 'Identitas toko berhasil diperbarui',
            'store' => $store,
        ]);
    }
}
