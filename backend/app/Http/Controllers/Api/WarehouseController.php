<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $warehouses = Warehouse::with('racks')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%');
            })
            ->when($request->has('is_active'), function ($query) use ($request) {
                $query->where('is_active', $request->is_active);
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($warehouses);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:warehouses,code',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $warehouse = Warehouse::create($request->all());

        return response()->json([
            'message' => 'Gudang berhasil ditambahkan',
            'warehouse' => $warehouse,
        ], 201);
    }

    public function show(Warehouse $warehouse)
    {
        $warehouse->load('racks');

        return response()->json($warehouse);
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $request->validate([
            'code' => 'required|string|unique:warehouses,code,' . $warehouse->id,
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $warehouse->update($request->all());

        return response()->json([
            'message' => 'Gudang berhasil diperbarui',
            'warehouse' => $warehouse,
        ]);
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();

        return response()->json([
            'message' => 'Gudang berhasil dihapus',
        ]);
    }
}
