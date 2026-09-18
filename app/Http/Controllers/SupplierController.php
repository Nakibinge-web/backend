<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SupplierController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => Supplier::all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'contact' => 'nullable|string|max:20',
            'email'   => 'nullable|email|max:255',
            'address' => 'nullable|string',
        ]);

        $supplier = Supplier::create($validated);

        return response()->json(['success' => true, 'message' => 'Supplier created successfully', 'data' => $supplier], 201);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $supplier]);
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $validated = $request->validate([
            'name'    => 'sometimes|required|string|max:255',
            'contact' => 'nullable|string|max:20',
            'email'   => 'nullable|email|max:255',
            'address' => 'nullable|string',
        ]);

        $supplier->update($validated);

        return response()->json(['success' => true, 'message' => 'Supplier updated successfully', 'data' => $supplier]);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $supplier->delete();

        return response()->json(['success' => true, 'message' => 'Supplier deleted successfully']);
    }
}
