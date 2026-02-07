<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            })
            ->when($request->has('is_active'), function ($query) use ($request) {
                $query->where('is_active', $request->is_active);
            })
            ->latest()
            ->paginate($request->per_page ?? 50);

        return response()->json($customers);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $code = $request->code;
        if (!$code) {
            $last = Customer::latest()->first();
            $num = $last ? (int) preg_replace('/[^0-9]/', '', $last->code ?? '0') + 1 : 1;
            $code = 'CUS-' . str_pad($num, 4, '0', STR_PAD_LEFT);
        }

        $customer = Customer::create([
            'code' => $code,
            'name' => $request->name,
            'phone' => $request->phone ?? null,
            'address' => $request->address ?? null,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'message' => 'Pelanggan berhasil ditambahkan',
            'customer' => $customer,
        ], 201);
    }

    public function show(Customer $customer)
    {
        return response()->json($customer);
    }

    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'code' => 'nullable|string|unique:customers,code,' . $customer->id,
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $customer->update($request->all());

        return response()->json([
            'message' => 'Pelanggan berhasil diperbarui',
            'customer' => $customer,
        ]);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return response()->json([
            'message' => 'Pelanggan berhasil dihapus',
        ]);
    }
}
