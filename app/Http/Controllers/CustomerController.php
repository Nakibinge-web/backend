<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->query('tenant_id');
        
        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant ID is required'
            ], 400);
        }

        $customers = Customer::where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $customer = Customer::create($validated);

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Customer created successfully'
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $tenantId = $request->query('tenant_id');
        
        $customer = Customer::where('tenant_id', $tenantId)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $customer
        ]);
    }

    public function update(Request $request, $id)
    {
        $tenantId = $request->input('tenant_id');
        
        $customer = Customer::where('tenant_id', $tenantId)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'status' => 'sometimes|required|in:active,inactive',
        ]);

        $customer->update($validated);

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Customer updated successfully'
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $tenantId = $request->query('tenant_id');
        
        $customer = Customer::where('tenant_id', $tenantId)
            ->findOrFail($id);

        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully'
        ]);
    }
}
