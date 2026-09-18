<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => Category::all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category = Category::create($validated);

        return response()->json(['success' => true, 'message' => 'Category created successfully', 'data' => $category], 201);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $category]);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category->update($validated);

        return response()->json(['success' => true, 'message' => 'Category updated successfully', 'data' => $category]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json(['success' => true, 'message' => 'Category deleted successfully']);
    }
}
