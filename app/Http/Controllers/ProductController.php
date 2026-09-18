<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    // ── SKU helpers ──────────────────────────────────────────────────────────

    /**
     * Build the two-letter prefix from the product name.
     * "Apple Watch" → "Ap", "iPhone" → "iP", "TV" → "TV"
     */
    private function skuPrefix(string $name): string
    {
        // Strip leading/trailing whitespace, collapse inner spaces
        $clean = trim(preg_replace('/\s+/', ' ', $name));
        if (strlen($clean) === 0) return 'XX';

        $words = explode(' ', $clean);
        if (count($words) >= 2) {
            // First letter of first word + first letter of second word, preserve original case
            return substr($words[0], 0, 1) . substr($words[1], 0, 1);
        }
        // Single word: first two chars
        return mb_substr($clean, 0, 2);
    }

    /**
     * Find the next available sequential SKU for the given prefix and tenant.
     * Returns e.g. "Ap-001", "Ap-002", …
     */
    private function nextSku(string $prefix, int $tenantId, ?int $excludeProductId = null): string
    {
        for ($n = 1; $n <= 9999; $n++) {
            $candidate = $prefix . '-' . str_pad($n, 3, '0', STR_PAD_LEFT);
            $query = Product::where('tenant_id', $tenantId)->where('sku', $candidate);
            if ($excludeProductId) {
                $query->where('id', '!=', $excludeProductId);
            }
            if (!$query->exists()) {
                return $candidate;
            }
        }
        // Fallback (should never happen in practice)
        return $prefix . '-' . uniqid();
    }

    // ── API: generate SKU preview ────────────────────────────────────────────

    public function generateSku(Request $request): JsonResponse
    {
        $name = trim($request->query('name', ''));
        if (!$name) {
            return response()->json(['success' => false, 'message' => 'name is required'], 422);
        }

        $prefix = $this->skuPrefix($name);
        $sku    = $this->nextSku($prefix, auth()->user()->tenant_id);

        return response()->json(['success' => true, 'sku' => $sku]);
    }

    public function index(): JsonResponse
    {
        $products = Product::with(['category', 'supplier'])->get();
        return response()->json(['success' => true, 'data' => $products]);
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $request->validate([
            'products'                    => 'required|array|min:1|max:50',
            'products.*.name'             => 'required|string|max:255',
            'products.*.sku'              => 'nullable|string|max:100',
            'products.*.barcode'          => 'nullable|string|max:100',
            'products.*.unit'             => 'nullable|string|max:50',
            'products.*.category_id'      => 'nullable|exists:categories,id',
            'products.*.new_category'     => 'nullable|string|max:255',
            'products.*.supplier_id'      => 'nullable|exists:suppliers,id',
            'products.*.stock'            => 'required|numeric|min:0',
            'products.*.cost_price'       => 'nullable|numeric|min:0',
            'products.*.price'            => 'required|numeric|min:0',
            'products.*.reorder_level'    => 'nullable|numeric|min:0',
            'products.*.description'      => 'nullable|string',
            'products.*.track_expiry'     => 'nullable|boolean',
            'products.*.manufacture_date' => 'nullable|date',
            'products.*.expiry_date'      => 'nullable|date',
            'images'                      => 'nullable|array',
            'images.*'                    => 'nullable|image|max:2048',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $created  = [];
        $errors   = [];

        \DB::transaction(function () use ($request, $tenantId, &$created, &$errors) {
            foreach ($request->input('products') as $index => $data) {
                try {
                    // Resolve category
                    $categoryId = $data['category_id'] ?? null;
                    if (empty($categoryId) && !empty($data['new_category'])) {
                        $cat        = Category::firstOrCreate(
                            ['name' => trim($data['new_category']), 'tenant_id' => $tenantId]
                        );
                        $categoryId = $cat->id;
                    }

                    $sku = !empty($data['sku'])
                        ? $data['sku']
                        : $this->nextSku($this->skuPrefix($data['name']), $tenantId);

                    // Handle per-product image upload
                    $imagePath = null;
                    if ($request->hasFile("images.$index")) {
                        $imagePath = $request->file("images.$index")->store('products', 'public');
                    }

                    $product = Product::create([
                        'tenant_id'        => $tenantId,
                        'name'             => $data['name'],
                        'sku'              => $sku,
                        'barcode'          => $data['barcode']          ?? null,
                        'unit'             => $data['unit']             ?? null,
                        'category_id'      => $categoryId,
                        'supplier_id'      => $data['supplier_id']      ?? null,
                        'stock'            => $data['stock'],
                        'cost_price'       => $data['cost_price']       ?? null,
                        'price'            => $data['price'],
                        'reorder_level'    => $data['reorder_level']    ?? 0,
                        'description'      => $data['description']      ?? null,
                        'track_expiry'     => $data['track_expiry']     ?? false,
                        'manufacture_date' => $data['manufacture_date'] ?? null,
                        'expiry_date'      => $data['expiry_date']      ?? null,
                        'image_path'       => $imagePath,
                    ]);

                    $created[] = $product->load(['category', 'supplier']);
                } catch (\Exception $e) {
                    $errors[] = ['index' => $index, 'name' => $data['name'] ?? "Product #$index", 'error' => $e->getMessage()];
                }
            }

            // Roll back everything if any product failed
            if (!empty($errors)) {
                throw new \Exception('One or more products failed to save.');
            }
        });

        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'Some products could not be saved.',
                'errors'  => $errors,
            ], 422);
        }

        return response()->json([
            'success'  => true,
            'message'  => count($created) . ' product(s) created successfully.',
            'data'     => $created,
        ], 201);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'sku'              => 'nullable|string|max:100',
            'barcode'          => 'nullable|string|max:100',
            'unit'             => 'nullable|string|max:50',
            'category_id'      => 'nullable|exists:categories,id',
            'new_category'     => 'nullable|string|max:255',
            'supplier_id'      => 'nullable|exists:suppliers,id',
            'stock'            => 'required|numeric|min:0',
            'cost_price'       => 'nullable|numeric|min:0',
            'price'            => 'required|numeric|min:0',
            'reorder_level'    => 'nullable|numeric|min:0',
            'description'      => 'nullable|string',
            'track_expiry'     => 'nullable|boolean',
            'manufacture_date' => 'nullable|date',
            'expiry_date'      => 'nullable|date|after_or_equal:manufacture_date',
            'image'            => 'nullable|image|max:2048',
        ]);

        // Create a new category on the fly if requested
        if (empty($validated['category_id']) && !empty($validated['new_category'])) {
            $category = Category::create([
                'name'      => $validated['new_category'],
                'tenant_id' => auth()->user()->tenant_id,
            ]);
            $validated['category_id'] = $category->id;
        }

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'tenant_id'        => auth()->user()->tenant_id,
            'name'             => $validated['name'],
            'sku'              => $validated['sku'] ?? $this->nextSku($this->skuPrefix($validated['name']), auth()->user()->tenant_id),
            'barcode'          => $validated['barcode'] ?? null,
            'unit'             => $validated['unit'] ?? null,
            'category_id'      => $validated['category_id'] ?? null,
            'supplier_id'      => $validated['supplier_id'] ?? null,
            'stock'            => $validated['stock'],
            'cost_price'       => $validated['cost_price'] ?? null,
            'price'            => $validated['price'],
            'reorder_level'    => $validated['reorder_level'] ?? 0,
            'description'      => $validated['description'] ?? null,
            'track_expiry'     => $validated['track_expiry'] ?? false,
            'manufacture_date' => $validated['manufacture_date'] ?? null,
            'expiry_date'      => $validated['expiry_date'] ?? null,
            'image_path'       => $imagePath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data'    => $product->load(['category', 'supplier']),
        ], 201);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $product->load(['category', 'supplier'])]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name'             => 'sometimes|required|string|max:255',
            'sku'              => 'nullable|string|max:100',
            'barcode'          => 'nullable|string|max:100',
            'unit'             => 'nullable|string|max:50',
            'category_id'      => 'nullable|exists:categories,id',
            'supplier_id'      => 'nullable|exists:suppliers,id',
            'stock'            => 'sometimes|required|numeric|min:0',
            'cost_price'       => 'nullable|numeric|min:0',
            'price'            => 'sometimes|required|numeric|min:0',
            'reorder_level'    => 'nullable|numeric|min:0',
            'description'      => 'nullable|string',
            'track_expiry'     => 'nullable|boolean',
            'manufacture_date' => 'nullable|date',
            'expiry_date'      => 'nullable|date|after_or_equal:manufacture_date',
            'image'            => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }

        unset($validated['image']);
        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data'    => $product->load(['category', 'supplier']),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
        $product->delete();
        return response()->json(['success' => true, 'message' => 'Product deleted successfully']);
    }

    public function getLowStock(): JsonResponse
    {
        $products = Product::with(['category', 'supplier'])
            ->whereColumn('stock', '<=', 'reorder_level')
            ->get();

        return response()->json(['success' => true, 'data' => $products]);
    }
}
