<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\Category;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Purchase::class);

        $purchases = Purchase::with(['supplier', 'purchaseItems.product'])->orderBy('purchase_date', 'desc')->get();

        return response()->json(['success' => true, 'data' => $purchases]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Purchase::class);

        $request->validate([
            'supplier_id'                       => 'nullable|exists:suppliers,id',
            'items'                             => 'required|array|min:1',
            'items.*.product_id'                => 'nullable|exists:products,id',
            'items.*.quantity'                  => 'required|integer|min:1',
            'items.*.cost_price'                => 'required|numeric|min:0',
            // new product fields (required when product_id is absent)
            'items.*.new_product'               => 'nullable|array',
            'items.*.new_product.name'          => 'required_with:items.*.new_product|string|max:255',
            'items.*.new_product.price'         => 'required_with:items.*.new_product|numeric|min:0',
            'items.*.new_product.sku'           => 'nullable|string|max:100',
            'items.*.new_product.barcode'       => 'nullable|string|max:100',
            'items.*.new_product.unit'          => 'nullable|string|max:50',
            'items.*.new_product.category_id'   => 'nullable|exists:categories,id',
            'items.*.new_product.new_category'  => 'nullable|string|max:255',
            'items.*.new_product.reorder_level' => 'nullable|numeric|min:0',
            'items.*.new_product.description'   => 'nullable|string|max:1000',
            'items.*.new_product.track_expiry'  => 'nullable|boolean',
            'items.*.new_product.manufacture_date' => 'nullable|date',
            'items.*.new_product.expiry_date'   => 'nullable|date|after_or_equal:items.*.new_product.manufacture_date',
        ]);

        $items      = $request->input('items');
        $supplierId = $request->input('supplier_id');
        $tenantId   = auth()->user()->tenant_id;

        // Validate: every item must have either product_id or new_product.name
        foreach ($items as $index => $item) {
            if (empty($item['product_id']) && empty($item['new_product']['name'])) {
                return response()->json([
                    'success' => false,
                    'message' => "Item #" . ($index + 1) . " must have either an existing product or a new product name.",
                ], 422);
            }
        }

        return DB::transaction(function () use ($items, $supplierId, $tenantId) {
            $newlyCreatedProducts = [];

            // Resolve product IDs — create new products where needed
            foreach ($items as &$item) {
                if (empty($item['product_id']) && !empty($item['new_product'])) {
                    $np = $item['new_product'];

                    // Create a new category on the fly if requested
                    $categoryId = $np['category_id'] ?? null;
                    if (empty($categoryId) && !empty($np['new_category'])) {
                        $category   = Category::create(['name' => $np['new_category'], 'tenant_id' => $tenantId]);
                        $categoryId = $category->id;
                    }

                    $product = Product::create([
                        'tenant_id'        => $tenantId,
                        'name'             => $np['name'],
                        'sku'              => !empty($np['sku'])
                            ? $np['sku']
                            : (function() use ($np, $tenantId) {
                                $prefix = mb_strlen(trim($np['name'])) >= 2
                                    ? (strpos(trim($np['name']), ' ') !== false
                                        ? substr(explode(' ', trim($np['name']))[0], 0, 1) . substr(explode(' ', trim($np['name']))[1], 0, 1)
                                        : mb_substr(trim($np['name']), 0, 2))
                                    : 'XX';
                                for ($n = 1; $n <= 9999; $n++) {
                                    $candidate = $prefix . '-' . str_pad($n, 3, '0', STR_PAD_LEFT);
                                    if (!Product::where('tenant_id', $tenantId)->where('sku', $candidate)->exists()) {
                                        return $candidate;
                                    }
                                }
                                return $prefix . '-' . uniqid();
                            })(),
                        'barcode'          => $np['barcode']          ?? null,
                        'unit'             => $np['unit']             ?? null,
                        'category_id'      => $categoryId,
                        'supplier_id'      => $supplierId,
                        'stock'            => 0, // will be incremented below
                        'cost_price'       => $item['cost_price'],
                        'price'            => $np['price'],
                        'reorder_level'    => $np['reorder_level']    ?? 0,
                        'description'      => $np['description']      ?? null,
                        'track_expiry'     => $np['track_expiry']     ?? false,
                        'manufacture_date' => $np['track_expiry'] ? ($np['manufacture_date'] ?? null) : null,
                        'expiry_date'      => $np['track_expiry'] ? ($np['expiry_date']       ?? null) : null,
                    ]);
                    $item['product_id'] = $product->id;
                    $newlyCreatedProducts[] = $product->load(['category', 'supplier']);
                }
            }
            unset($item);

            $totalAmount = array_sum(array_map(fn($i) => $i['quantity'] * $i['cost_price'], $items));

            $purchase = Purchase::create([
                'supplier_id'   => $supplierId,
                'total_amount'  => $totalAmount,
                'purchase_date' => now(),
            ]);

            foreach ($items as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'cost_price'  => $item['cost_price'],
                ]);

                Product::findOrFail($item['product_id'])->increment('stock', $item['quantity']);

                StockMovement::create([
                    'tenant_id'      => $tenantId,
                    'product_id'     => $item['product_id'],
                    'type'           => 'IN',
                    'quantity'       => $item['quantity'],
                    'reference_id'   => $purchase->id,
                    'reference_type' => 'purchase',
                    'date'           => now(),
                ]);
            }

            $purchase->load(['supplier', 'purchaseItems.product']);

            return response()->json([
                'success'              => true,
                'message'              => 'Purchase recorded successfully',
                'data'                 => $purchase,
                'new_products'         => $newlyCreatedProducts,
            ], 201);
        });
    }

    public function show(Purchase $purchase): JsonResponse
    {
        $this->authorize('view', $purchase);

        $purchase->load(['supplier', 'purchaseItems.product']);

        return response()->json(['success' => true, 'data' => $purchase]);
    }

    public function update(Request $request, Purchase $purchase): JsonResponse
    {
        $this->authorize('update', $purchase);

        $request->validate([
            'supplier_id'    => 'nullable|exists:suppliers,id',
            'purchase_date'  => 'sometimes|required|date',
            'items'          => 'sometimes|required|array|min:1',
            'items.*.product_id'  => 'required_with:items|exists:products,id',
            'items.*.quantity'    => 'required_with:items|integer|min:1',
            'items.*.cost_price'  => 'required_with:items|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request, $purchase) {
            // If items are being updated, reverse old stock movements first
            if ($request->has('items')) {
                foreach ($purchase->purchaseItems as $oldItem) {
                    Product::findOrFail($oldItem->product_id)->decrement('stock', $oldItem->quantity);
                    StockMovement::where('reference_id', $purchase->id)
                        ->where('product_id', $oldItem->product_id)
                        ->delete();
                }

                $purchase->purchaseItems()->delete();

                $items = $request->input('items');
                $totalAmount = array_sum(array_map(fn($i) => $i['quantity'] * $i['cost_price'], $items));

                foreach ($items as $item) {
                    PurchaseItem::create([
                        'purchase_id' => $purchase->id,
                        'product_id'  => $item['product_id'],
                        'quantity'    => $item['quantity'],
                        'cost_price'  => $item['cost_price'],
                    ]);

                    Product::findOrFail($item['product_id'])->increment('stock', $item['quantity']);

                    StockMovement::create([
                        'tenant_id'      => auth()->user()->tenant_id,
                        'product_id'     => $item['product_id'],
                        'type'           => 'IN',
                        'quantity'       => $item['quantity'],
                        'reference_id'   => $purchase->id,
                        'reference_type' => 'purchase',
                        'date'           => $request->input('purchase_date', $purchase->purchase_date),
                    ]);
                }

                $purchase->update([
                    'supplier_id'   => $request->input('supplier_id', $purchase->supplier_id),
                    'total_amount'  => $totalAmount,
                    'purchase_date' => $request->input('purchase_date', $purchase->purchase_date),
                ]);
            } else {
                $purchase->update($request->only('supplier_id', 'purchase_date'));
            }

            $purchase->load(['supplier', 'purchaseItems.product']);

            return response()->json([
                'success' => true,
                'message' => 'Purchase updated successfully.',
                'data'    => $purchase,
            ]);
        });
    }

    public function destroy(Purchase $purchase): JsonResponse
    {
        $this->authorize('delete', $purchase);

        return DB::transaction(function () use ($purchase) {
            // Reverse stock for every item in this purchase
            foreach ($purchase->purchaseItems as $item) {
                Product::findOrFail($item->product_id)->decrement('stock', $item->quantity);
            }

            // Remove associated stock movements
            StockMovement::where('reference_id', $purchase->id)->delete();

            $purchase->purchaseItems()->delete();
            $purchase->delete();

            return response()->json([
                'success' => true,
                'message' => 'Purchase deleted and stock reversed successfully.',
            ]);
        });
    }

    public function getMonthlyReport(Request $request): JsonResponse
    {
        $user = Auth::user();
        $isPrivileged = $user->roles()->pluck('name')
                            ->intersect(['owner', 'admin', 'manager'])
                            ->isNotEmpty();

        if (!$isPrivileged && !$user->hasPermission('purchases.report')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to view purchase reports.'], 403);
        }

        $validated = $request->validate(['month' => 'required|date_format:Y-m']);

        [$year, $month] = explode('-', $validated['month']);

        $purchases = Purchase::whereYear('purchase_date', $year)
                             ->whereMonth('purchase_date', $month)
                             ->with(['supplier', 'purchaseItems.product'])
                             ->get();

        return response()->json(['success' => true, 'data' => [
            'month'              => $validated['month'],
            'total_purchases'    => $purchases->sum('total_amount'),
            'total_transactions' => $purchases->count(),
            'purchases'          => $purchases,
        ]]);
    }

    public function getDailyReport(Request $request): JsonResponse
    {
        $user = Auth::user();
        $isPrivileged = $user->roles()->pluck('name')
                            ->intersect(['owner', 'admin', 'manager'])
                            ->isNotEmpty();

        if (!$isPrivileged && !$user->hasPermission('purchases.report')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to view purchase reports.'], 403);
        }

        $validated = $request->validate(['date' => 'required|date_format:Y-m-d']);

        $purchases = Purchase::whereDate('purchase_date', $validated['date'])
                             ->with(['supplier', 'purchaseItems.product'])
                             ->orderBy('purchase_date', 'desc')
                             ->get();

        // Aggregate totals per supplier for a quick breakdown
        $bySupplier = $purchases->groupBy(fn($p) => $p->supplier?->name ?? 'Unknown')
            ->map(fn($group, $name) => [
                'supplier'           => $name,
                'transactions'       => $group->count(),
                'total_amount'       => $group->sum('total_amount'),
            ])
            ->values();

        // Total items purchased across all orders
        $totalItems = $purchases->sum(fn($p) => $p->purchaseItems->count());

        return response()->json(['success' => true, 'data' => [
            'date'               => $validated['date'],
            'total_amount'       => $purchases->sum('total_amount'),
            'total_transactions' => $purchases->count(),
            'total_items'        => $totalItems,
            'by_supplier'        => $bySupplier,
            'purchases'          => $purchases,
        ]]);
    }
}
