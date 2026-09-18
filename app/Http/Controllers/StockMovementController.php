<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockMovementController extends Controller
{
    public function index(): JsonResponse
    {
        $movements = StockMovement::with('product')->orderBy('date', 'desc')->get();

        return response()->json(['success' => true, 'data' => $movements]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type'       => 'required|in:IN,OUT,ADJUSTMENT',
            'quantity'   => 'required|integer|min:1',
            'reason'     => 'nullable|string|max:500',
            'date'       => 'nullable|date',
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                $product = Product::findOrFail($validated['product_id']);

                if ($validated['type'] === 'OUT') {
                    if ($product->stock < $validated['quantity']) {
                        throw new \Exception("Insufficient stock. Available: {$product->stock}");
                    }
                    $product->decrement('stock', $validated['quantity']);
                } elseif ($validated['type'] === 'IN') {
                    $product->increment('stock', $validated['quantity']);
                } elseif ($validated['type'] === 'ADJUSTMENT') {
                    // quantity field holds the new absolute stock level
                    $oldStock = $product->stock;
                    $product->update(['stock' => $validated['quantity']]);
                    $validated['quantity'] = abs($validated['quantity'] - $oldStock);
                }

                $movement = StockMovement::create([
                    'tenant_id'      => Auth::user()->tenant_id,
                    'product_id'     => $validated['product_id'],
                    'type'           => $validated['type'],
                    'quantity'       => $validated['quantity'],
                    'reference_id'   => 'manual',
                    'reference_type' => 'manual',
                    'reason'         => $validated['reason'] ?? null,
                    'date'           => $validated['date'] ?? now()->toDateString(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Stock updated successfully.',
                    'data'    => $movement->load('product'),
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function show(StockMovement $stockMovement): JsonResponse
    {
        $stockMovement->load('product');

        return response()->json(['success' => true, 'data' => $stockMovement]);
    }

    public function getByProduct(Request $request, $productId): JsonResponse
    {
        $movements = StockMovement::where('product_id', $productId)
                                  ->with('product')
                                  ->orderBy('date', 'desc')
                                  ->get();

        return response()->json(['success' => true, 'data' => $movements]);
    }

    public function getByType(Request $request): JsonResponse
    {
        $validated = $request->validate(['type' => 'required|in:IN,OUT,ADJUSTMENT']);

        $movements = StockMovement::where('type', $validated['type'])
                                  ->with('product')
                                  ->orderBy('date', 'desc')
                                  ->get();

        return response()->json(['success' => true, 'data' => $movements]);
    }

    public function getByDateRange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $movements = StockMovement::whereBetween('date', [$validated['start_date'], $validated['end_date']])
                                  ->with('product')
                                  ->orderBy('date', 'desc')
                                  ->get();

        return response()->json(['success' => true, 'data' => $movements]);
    }
}
