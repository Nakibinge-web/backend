<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    // ── List ─────────────────────────────────────────────────────────────────

    public function index(): JsonResponse
    {
        $user = Auth::user();

        $isPrivileged = $user->roles()->pluck('name')
            ->intersect(['owner', 'admin', 'manager'])
            ->isNotEmpty();

        $query = Invoice::with('user:id,name,email')
            ->orderBy('invoice_date', 'desc')
            ->orderBy('id', 'desc');

        // Non-privileged users only see their own invoices
        if (!$isPrivileged) {
            $query->where('user_id', $user->id);
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_date'     => 'required|date',
            'due_date'         => 'nullable|date',
            'source_ref'       => 'nullable|string|max:100',
            'customer_name'    => 'required|string|max:255',
            'customer_phone'   => 'nullable|string|max:50',
            'customer_email'   => 'nullable|email|max:255',
            'customer_address' => 'nullable|string|max:500',
            'items'            => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity'    => 'required|numeric|min:0.01',
            'items.*.price'       => 'required|numeric|min:0',
            'items.*.subtotal'    => 'nullable|numeric|min:0',
            'discount_amount'  => 'nullable|numeric|min:0',
            'tax_amount'       => 'nullable|numeric|min:0',
            'amount_paid'      => 'nullable|numeric|min:0',
            'payment_status'   => 'nullable|in:paid,partial,due',
            'notes'            => 'nullable|string|max:2000',
        ]);

        // Calculate subtotal from items
        $subtotal = array_sum(array_map(
            fn($i) => ($i['subtotal'] ?? (floatval($i['quantity']) * floatval($i['price']))),
            $validated['items']
        ));

        $discount    = floatval($validated['discount_amount'] ?? 0);
        $tax         = floatval($validated['tax_amount'] ?? 0);
        $totalAmount = max(0, $subtotal - $discount + $tax);
        $amountPaid  = isset($validated['amount_paid']) ? floatval($validated['amount_paid']) : $totalAmount;

        // Derive payment status if not explicitly provided
        $paymentStatus = $validated['payment_status'] ?? ($amountPaid >= $totalAmount ? 'paid' : ($amountPaid > 0 ? 'partial' : 'due'));

        // Generate a unique invoice number
        $invoiceNumber = $this->generateInvoiceNumber();

        // Normalise each item's subtotal
        $items = array_map(function ($i) {
            $qty = floatval($i['quantity']);
            $price = floatval($i['price']);
            return [
                'description' => $i['description'],
                'quantity'    => $qty,
                'price'       => $price,
                'subtotal'    => $i['subtotal'] ?? ($qty * $price),
            ];
        }, $validated['items']);

        $invoice = Invoice::create([
            'user_id'          => Auth::id(),
            'invoice_number'   => $invoiceNumber,
            'invoice_date'     => $validated['invoice_date'],
            'due_date'         => $validated['due_date'] ?? $validated['invoice_date'],
            'source_ref'       => $validated['source_ref'] ?? null,
            'customer_name'    => $validated['customer_name'],
            'customer_phone'   => $validated['customer_phone'] ?? null,
            'customer_email'   => $validated['customer_email'] ?? null,
            'customer_address' => $validated['customer_address'] ?? null,
            'subtotal'         => $subtotal,
            'discount_amount'  => $discount,
            'tax_amount'       => $tax,
            'total_amount'     => $totalAmount,
            'amount_paid'      => $amountPaid,
            'payment_status'   => $paymentStatus,
            'items'            => $items,
            'notes'            => $validated['notes'] ?? null,
        ]);

        return response()->json(['success' => true, 'message' => 'Invoice created successfully.', 'data' => $invoice], 201);
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show(Invoice $invoice): JsonResponse
    {
        $user = Auth::user();

        $isPrivileged = $user->roles()->pluck('name')
            ->intersect(['owner', 'admin', 'manager'])
            ->isNotEmpty();

        if (!$isPrivileged && $invoice->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to view this invoice.'], 403);
        }

        return response()->json(['success' => true, 'data' => $invoice->load('user:id,name,email')]);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        $user = Auth::user();

        $isPrivileged = $user->roles()->pluck('name')
            ->intersect(['owner', 'admin', 'manager'])
            ->isNotEmpty();

        if (!$isPrivileged && $invoice->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to update this invoice.'], 403);
        }

        $validated = $request->validate([
            'invoice_date'     => 'sometimes|date',
            'due_date'         => 'nullable|date',
            'source_ref'       => 'nullable|string|max:100',
            'customer_name'    => 'sometimes|string|max:255',
            'customer_phone'   => 'nullable|string|max:50',
            'customer_email'   => 'nullable|email|max:255',
            'customer_address' => 'nullable|string|max:500',
            'items'            => 'sometimes|array|min:1',
            'items.*.description' => 'required_with:items|string|max:500',
            'items.*.quantity'    => 'required_with:items|numeric|min:0.01',
            'items.*.price'       => 'required_with:items|numeric|min:0',
            'discount_amount'  => 'nullable|numeric|min:0',
            'tax_amount'       => 'nullable|numeric|min:0',
            'amount_paid'      => 'nullable|numeric|min:0',
            'payment_status'   => 'nullable|in:paid,partial,due',
            'notes'            => 'nullable|string|max:2000',
        ]);

        if (!empty($validated['items'])) {
            $subtotal = array_sum(array_map(
                fn($i) => floatval($i['quantity']) * floatval($i['price']),
                $validated['items']
            ));
            $validated['subtotal'] = $subtotal;

            $discount = floatval($validated['discount_amount'] ?? $invoice->discount_amount);
            $tax      = floatval($validated['tax_amount'] ?? $invoice->tax_amount);
            $validated['total_amount'] = max(0, $subtotal - $discount + $tax);

            $validated['items'] = array_map(function ($i) {
                $qty   = floatval($i['quantity']);
                $price = floatval($i['price']);
                return ['description' => $i['description'], 'quantity' => $qty, 'price' => $price, 'subtotal' => $qty * $price];
            }, $validated['items']);
        }

        $invoice->fill($validated)->save();

        return response()->json(['success' => true, 'message' => 'Invoice updated successfully.', 'data' => $invoice]);
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function destroy(Invoice $invoice): JsonResponse
    {
        $user = Auth::user();

        $isPrivileged = $user->roles()->pluck('name')
            ->intersect(['owner', 'admin', 'manager'])
            ->isNotEmpty();

        if (!$isPrivileged && $invoice->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to delete this invoice.'], 403);
        }

        $invoice->delete();

        return response()->json(['success' => true, 'message' => 'Invoice deleted successfully.']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function generateInvoiceNumber(): string
    {
        $year  = date('y');
        $nextYear = (int)$year + 1;
        $prefix = "INV/{$year}-{$nextYear}/";

        // Find the highest existing number for this financial year prefix
        $last = Invoice::where('invoice_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->value('invoice_number');

        if ($last) {
            $seq = (int) substr($last, strlen($prefix)) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
