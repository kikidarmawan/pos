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
                'paper_width' => 80,
                'font_size' => 'normal',
                'show_store_header' => true,
                'default_printer_name' => null,
            ]);
        }
        return response()->json($store);
    }

    /**
     * Update identitas toko dan/atau pengaturan printer
     */
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'paper_width' => 'sometimes|in:58,80',
            'font_size' => 'sometimes|in:small,normal,large',
            'show_store_header' => 'sometimes|boolean',
            'default_printer_name' => 'nullable|string|max:255',
        ]);

        $store = Store::current();
        $keys = ['name', 'address', 'phone', 'email', 'paper_width', 'font_size', 'show_store_header', 'default_printer_name'];
        $data = $request->only($keys);

        if (!$store) {
            $store = Store::create([
                'name' => $data['name'] ?? 'Toko Saya',
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'paper_width' => $data['paper_width'] ?? 80,
                'font_size' => $data['font_size'] ?? 'normal',
                'show_store_header' => $data['show_store_header'] ?? true,
                'default_printer_name' => $data['default_printer_name'] ?? null,
            ]);
            return response()->json([
                'message' => 'Identitas toko berhasil disimpan',
                'store' => $store,
            ], 201);
        }

        $store->update($data);

        return response()->json([
            'message' => 'Data berhasil diperbarui',
            'store' => $store,
        ]);
    }
}
