<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $vehicles = Vehicle::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('license_plate', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($request->per_page ?? 50);

        return response()->json($vehicles);
    }

    public function store(Request $request)
    {
        $request->validate([
            'license_plate' => 'required|string|unique:vehicles,license_plate',
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
        ]);

        $vehicle = Vehicle::create($request->all());

        return response()->json([
            'message' => 'Kendaraan berhasil ditambahkan',
            'vehicle' => $vehicle,
        ], 201);
    }

    public function show(Vehicle $vehicle)
    {
        return response()->json($vehicle);
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $request->validate([
            'license_plate' => 'required|string|unique:vehicles,license_plate,' . $vehicle->id,
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
        ]);

        $vehicle->update($request->all());

        return response()->json([
            'message' => 'Kendaraan berhasil diperbarui',
            'vehicle' => $vehicle,
        ]);
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();

        return response()->json([
            'message' => 'Kendaraan berhasil dihapus',
        ]);
    }
}
