<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with(['category', 'baseUnit', 'productUnits.unit'])
            ->when(isset($request->search), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%')
                        ->orWhere('barcode', 'like', '%' . $search . '%');
                });
            })
            ->when(isset($request->category_id), function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when(isset($request->is_active), function ($query) use ($request) {
                $query->where('is_active', $request->is_active);
            })
            ->when($request->warehouse_id, function ($query) use ($request) {
                $query->withSum(['stocks as total_stock' => fn($q) => $q->where('warehouse_id', $request->warehouse_id)], 'quantity');
            }, function ($query) {
                $query->withSum('stocks as total_stock', 'quantity');
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        return ProductResource::collection($products);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'code' => 'required|string|unique:products,code',
            'name' => 'required|string|max:255',
            'barcode' => 'nullable|string',
            'description' => 'nullable|string',
            'base_unit_id' => 'required|exists:units,id',
            'base_price' => 'required|numeric|min:0',
            'minimum_stock' => 'nullable|numeric|min:0',
            'is_active' => 'required|boolean',
            'units' => 'required|array|min:1',
            'units.*.unit_id' => 'required|exists:units,id',
            'units.*.conversion_factor' => 'required|numeric|min:0.001',
            'units.*.selling_price' => 'required|numeric|min:0',
            'units.*.barcode' => 'nullable|string',
            'units.*.is_default' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $product = Product::create([
                'category_id' => $validated['category_id'],
                'code' => $validated['code'],
                'name' => $validated['name'],
                'barcode' => $validated['barcode'] ?? null,
                'description' => $validated['description'] ?? null,
                'base_unit_id' => $validated['base_unit_id'],
                'base_price' => $validated['base_price'],
                'minimum_stock' => $validated['minimum_stock'] ?? 0,
                'is_active' => $validated['is_active'],
            ]);

            // Save product units (multi-satuan)
            foreach ($validated['units'] as $unitData) {
                ProductUnit::create([
                    'product_id' => $product->id,
                    'unit_id' => $unitData['unit_id'],
                    'conversion_factor' => $unitData['conversion_factor'],
                    'selling_price' => $unitData['selling_price'],
                    'barcode' => $unitData['barcode'] ?? null,
                    'is_default' => $unitData['is_default'] ?? false,
                ]);
            }

            DB::commit();

            $product->load(['category', 'baseUnit', 'productUnits.unit']);
            $product->loadSum('stocks as total_stock', 'quantity');

            return (new ProductResource($product))->additional([
                'message' => 'Produk berhasil ditambahkan',
            ])->response()->setStatusCode(201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menambahkan produk',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Product $product)
    {
        $product->load(['category', 'baseUnit', 'productUnits.unit', 'stocks.warehouse', 'stocks.rack']);
        $product->loadSum('stocks as total_stock', 'quantity');

        return new ProductResource($product);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'code' => 'required|string|unique:products,code,' . $product->id,
            'name' => 'required|string|max:255',
            'barcode' => 'nullable|string',
            'description' => 'nullable|string',
            'base_unit_id' => 'required|exists:units,id',
            'base_price' => 'required|numeric|min:0',
            'minimum_stock' => 'nullable|numeric|min:0',
            'is_active' => 'required|boolean',
            'units' => 'required|array|min:1',
            'units.*.unit_id' => 'required|exists:units,id',
            'units.*.conversion_factor' => 'required|numeric|min:0.001',
            'units.*.selling_price' => 'required|numeric|min:0',
            'units.*.barcode' => 'nullable|string',
            'units.*.is_default' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $product->update([
                'category_id' => $validated['category_id'],
                'code' => $validated['code'],
                'name' => $validated['name'],
                'barcode' => $validated['barcode'] ?? null,
                'description' => $validated['description'] ?? null,
                'base_unit_id' => $validated['base_unit_id'],
                'base_price' => $validated['base_price'],
                'minimum_stock' => $validated['minimum_stock'] ?? 0,
                'is_active' => $validated['is_active'],
            ]);

            // Delete old product units and create new ones
            $product->productUnits()->delete();

            foreach ($validated['units'] as $unitData) {
                ProductUnit::create([
                    'product_id' => $product->id,
                    'unit_id' => $unitData['unit_id'],
                    'conversion_factor' => $unitData['conversion_factor'],
                    'selling_price' => $unitData['selling_price'],
                    'barcode' => $unitData['barcode'] ?? null,
                    'is_default' => $unitData['is_default'] ?? false,
                ]);
            }

            DB::commit();

            $product->load(['category', 'baseUnit', 'productUnits.unit']);
            $product->loadSum('stocks as total_stock', 'quantity');

            return (new ProductResource($product))->additional([
                'message' => 'Produk berhasil diperbarui',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal memperbarui produk',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'message' => 'Produk berhasil dihapus',
        ]);
    }

    public function searchBarcode(Request $request)
    {
        $barcode = $request->barcode;

        // Search in products
        $product = Product::where('barcode', $barcode)->first();

        if (!$product) {
            // Search in product_units
            $productUnit = ProductUnit::where('barcode', $barcode)
                ->with('product', 'unit')
                ->first();

            if ($productUnit) {
                return response()->json([
                    'product' => $productUnit->product,
                    'unit' => $productUnit->unit,
                    'product_unit' => $productUnit,
                ]);
            }
        }

        if ($product) {
            $product->load(['baseUnit', 'productUnits.unit']);
            return response()->json([
                'product' => $product,
                'unit' => $product->baseUnit,
                'product_unit' => $product->productUnits->where('is_default', true)->first(),
            ]);
        }

        return response()->json([
            'message' => 'Produk tidak ditemukan',
        ], 404);
    }
}
