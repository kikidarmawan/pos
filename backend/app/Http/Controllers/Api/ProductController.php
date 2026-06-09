<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with(['category', 'baseUnit', 'productUnits.unit'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%')
                        ->orWhere('barcode', 'like', '%' . $search . '%')
                        ->orWhereHas('productUnits', function ($qUnit) use ($search) {
                            $qUnit->where('barcode', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($request->barcode, function ($query, $barcode) {
                $query->where(function ($q) use ($barcode) {
                    $q->where('barcode', '=', $barcode)
                        ->orWhere('code', '=', $barcode)
                        ->orWhereHas('productUnits', function ($qUnit) use ($barcode) {
                            $qUnit->where('barcode', '=', $barcode);
                        });
                });
            })
            ->when($request->category_id, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($request->is_active, function ($query) use ($request) {
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
        $request->merge(['units' => $this->parseUnits($request->units)]);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'code' => 'required|string|unique:products,code',
            'name' => 'required|string|max:255',
            'barcode' => 'nullable|string|unique:products,barcode',
            'description' => 'nullable|string',
            'base_unit_id' => 'required|exists:units,id',
            'base_price' => 'required|numeric|min:0',
            'minimum_stock' => 'nullable|numeric|min:0',
            'is_active' => 'required|boolean',
            'units' => 'required|array|min:1',
            'units.*.unit_id' => 'required|exists:units,id',
            'units.*.conversion_factor' => 'required|numeric|min:0.001',
            'units.*.selling_price' => 'required|numeric|min:0',
            'units.*.barcode' => 'nullable|string|distinct|unique:product_units,barcode',
            'units.*.is_default' => 'nullable|boolean',
        ]);

        $units = $validated['units'];

        DB::beginTransaction();
        try {
            $imagePath = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                if (!$file->isValid()) {
                    return response()->json([
                        'message' => 'File gambar tidak valid.',
                        'errors' => ['image' => ['File gambar tidak valid.']],
                    ], 422);
                }
                if ($file->getSize() > 2048 * 1024) {
                    return response()->json([
                        'message' => 'Ukuran gambar maksimal 2 MB.',
                        'errors' => ['image' => ['Ukuran gambar maksimal 2 MB.']],
                    ], 422);
                }
                if (!$this->isAllowedImageExtension($file)) {
                    return response()->json([
                        'message' => 'Gambar harus berformat JPG, PNG, atau GIF.',
                        'errors' => ['image' => ['Gambar harus berformat JPG, PNG, atau GIF.']],
                    ], 422);
                }
                $imagePath = $file->store('products', 'public');
            }

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
                'image' => $imagePath,
            ]);

            // Save product units (multi-satuan)
            foreach ($units as $unitData) {
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
        $request->merge(['units' => $this->parseUnits($request->units)]);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'code' => 'required|string|unique:products,code,' . $product->id,
            'name' => 'required|string|max:255',
            'barcode' => 'nullable|string|unique:products,barcode,' . $product->id . ',id',
            'description' => 'nullable|string',
            'base_unit_id' => 'required|exists:units,id',
            'base_price' => 'required|numeric|min:0',
            'minimum_stock' => 'nullable|numeric|min:0',
            'is_active' => 'required|boolean',
            'remove_image' => 'nullable|boolean',
            'units' => 'required|array|min:1',
            'units.*.unit_id' => 'required|exists:units,id',
            'units.*.conversion_factor' => 'required|numeric|min:0.001',
            'units.*.selling_price' => 'required|numeric|min:0',
            'units.*.barcode' => [
                'nullable',
                'string',
                'distinct',
                Rule::unique('product_units', 'barcode')->where(function ($query) use ($product) {
                    return $query->where('product_id', '!=', $product->id);
                }),
            ],
            'units.*.is_default' => 'nullable|boolean',
        ]);

        $units = $validated['units'];

        DB::beginTransaction();
        try {
            $imagePath = $product->image;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                if (!$file->isValid()) {
                    return response()->json([
                        'message' => 'File gambar tidak valid.',
                        'errors' => ['image' => ['File gambar tidak valid.']],
                    ], 422);
                }
                if ($file->getSize() > 2048 * 1024) {
                    return response()->json([
                        'message' => 'Ukuran gambar maksimal 2 MB.',
                        'errors' => ['image' => ['Ukuran gambar maksimal 2 MB.']],
                    ], 422);
                }
                if (!$this->isAllowedImageExtension($file)) {
                    return response()->json([
                        'message' => 'Gambar harus berformat JPG, PNG, atau GIF.',
                        'errors' => ['image' => ['Gambar harus berformat JPG, PNG, atau GIF.']],
                    ], 422);
                }
                if ($product->image && Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                }
                $imagePath = $request->file('image')->store('products', 'public');
            } elseif ($request->boolean('remove_image') && $product->image) {
                if (Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                }
                $imagePath = null;
            }

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
                'image' => $imagePath,
            ]);

            // Delete old product units and create new ones
            $product->productUnits()->delete();

            foreach ($units as $unitData) {
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

    /**
     * Check if uploaded file has allowed image extension (by client filename).
     */
    private function isAllowedImageExtension($file): bool
    {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower($file->getClientOriginalExtension());

        return in_array($ext, $allowed, true);
    }

    /**
     * Parse units from request (supports JSON string from FormData).
     */
    private function parseUnits($units): array
    {
        if (is_string($units)) {
            $decoded = json_decode($units, true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_array($units) ? $units : [];
    }
}
