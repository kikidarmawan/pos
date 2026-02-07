<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rack;
use Illuminate\Http\Request;

class RackController extends Controller
{
    public function index(Request $request)
    {
        $racks = Rack::with('warehouse')
            ->when($request->warehouse_id, function ($query, $warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            })
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%');
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($racks);
    }

    public function store(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'code' => 'required|string',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $rack = Rack::create($request->all());
        $rack->load('warehouse');

        return response()->json([
            'message' => 'Rak berhasil ditambahkan',
            'rack' => $rack,
        ], 201);
    }

    public function show(Rack $rack)
    {
        $rack->load('warehouse');

        return response()->json($rack);
    }

    public function update(Request $request, Rack $rack)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'code' => 'required|string',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $rack->update($request->all());
        $rack->load('warehouse');

        return response()->json([
            'message' => 'Rak berhasil diperbarui',
            'rack' => $rack,
        ]);
    }

    public function destroy(Rack $rack)
    {
        $rack->delete();

        return response()->json([
            'message' => 'Rak berhasil dihapus',
        ]);
    }
}
