<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        $units = Unit::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%');
            })
            ->when($request->has('is_active'), function ($query) use ($request) {
                $query->where('is_active', $request->is_active);
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($units);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:units,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $unit = Unit::create($request->all());

        return response()->json([
            'message' => 'Satuan berhasil ditambahkan',
            'unit' => $unit,
        ], 201);
    }

    public function show(Unit $unit)
    {
        return response()->json($unit);
    }

    public function update(Request $request, Unit $unit)
    {
        $request->validate([
            'code' => 'required|string|unique:units,code,' . $unit->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $unit->update($request->all());

        return response()->json([
            'message' => 'Satuan berhasil diperbarui',
            'unit' => $unit,
        ]);
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();

        return response()->json([
            'message' => 'Satuan berhasil dihapus',
        ]);
    }
}
