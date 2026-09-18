<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class AiController extends Controller
{
    /**
     * POST /api/ai/chat
     * Accepts { question: string } and returns an AI-generated answer
     * grounded in the tenant's actual business data.
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate(['question' => 'required|string|max:1000']);

        $apiKey = config('services.mistral.key');
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'AI service is not configured. Please set MISTRAL_API_KEY in your .env file.',
            ], 503);
        }

        $context = $this->buildContext();

        $systemPrompt = <<<PROMPT
You are a smart financial and business analyst assistant embedded in an inventory and sales management system.
You help small business owners understand their financial performance, spot trends, and make better decisions.

Always:
- Be concise but thorough. Use bullet points for lists.
- Format currency as UGX with comma separators (e.g. UGX 1,200,000).
- When asked to forecast, use the historical data provided and state your assumptions clearly.
- If the data is insufficient for a reliable forecast, say so honestly and suggest what data would help.
- Keep a friendly, professional tone — like a trusted business advisor.

Here is the current business data snapshot:

{$context}
PROMPT;

        $payload = [
            'model'       => 'mistral-small-latest',
            'messages'    => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $request->question],
            ],
            'temperature' => 0.4,
            'max_tokens'  => 1024,
        ];

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])
            ->post('https://api.mistral.ai/v1/chat/completions', $payload);

        if (!$response->successful()) {
            $errorMsg = $response->json('message') ?? $response->json('error.message') ?? 'Unknown error';
            $status   = $response->status();

            if ($status === 429) {
                return response()->json([
                    'success' => false,
                    'message' => 'The AI service is temporarily rate-limited. Please wait a moment and try again.',
                ], 429);
            }

            return response()->json([
                'success' => false,
                'message' => "AI service error: {$errorMsg}",
            ], 502);
        }

        $text = $response->json('choices.0.message.content') ?? 'No response generated.';

        return response()->json(['success' => true, 'answer' => $text]);
    }

    /**
     * Build a rich text context snapshot from the tenant's live data.
     */
    private function buildContext(): string
    {
        $tenantId = Auth::user()->tenant_id;
        $now      = Carbon::now();

        // ── Sales snapshots ──────────────────────────────────────────
        $allSales = Sale::where('tenant_id', $tenantId)->get();

        $salesToday    = $allSales->filter(fn($s) => Carbon::parse($s->sale_date)->isToday());
        $salesThisWeek = $allSales->filter(fn($s) => Carbon::parse($s->sale_date)->isCurrentWeek());
        $salesThisMon  = $allSales->filter(fn($s) => Carbon::parse($s->sale_date)->isCurrentMonth());
        $salesLastMon  = $allSales->filter(fn($s) => Carbon::parse($s->sale_date)->month === $now->copy()->subMonth()->month
                                                   && Carbon::parse($s->sale_date)->year  === $now->copy()->subMonth()->year);

        // Monthly trend — last 6 months
        $monthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $label = $month->format('M Y');
            $total = $allSales
                ->filter(fn($s) => Carbon::parse($s->sale_date)->month === (int)$month->month
                                && Carbon::parse($s->sale_date)->year  === (int)$month->year)
                ->sum('total_amount');
            $monthlyTrend[] = "{$label}: UGX " . number_format($total, 0);
        }

        // ── Purchases ────────────────────────────────────────────────
        $allPurchases  = Purchase::where('tenant_id', $tenantId)->get();
        $purchThisMon  = $allPurchases->filter(fn($p) => Carbon::parse($p->purchase_date ?? $p->created_at)->isCurrentMonth());

        // ── Products ─────────────────────────────────────────────────
        $products   = Product::where('tenant_id', $tenantId)->get();
        $lowStock   = $products->filter(fn($p) => $p->stock <= $p->reorder_level && $p->stock > 0);
        $outOfStock = $products->filter(fn($p) => $p->stock <= 0);

        // Top 5 products by revenue (using sale_items)
        $productRevenue = SaleItem::whereHas('sale', fn($q) => $q->where('tenant_id', $tenantId))
            ->with('product:id,name')
            ->get()
            ->groupBy('product_id')
            ->map(fn($items) => [
                'name'    => $items->first()->product->name ?? "Product #{$items->first()->product_id}",
                'revenue' => $items->sum('subtotal'),
                'qty'     => $items->sum('quantity'),
            ])
            ->sortByDesc('revenue')
            ->take(5);

        // ── Profit estimate ──────────────────────────────────────────
        $totalRevenue  = $allSales->where('sale_date', '>=', $now->copy()->startOfMonth())->sum('total_amount');
        $totalCosts    = $purchThisMon->sum('total_amount');
        $grossProfit   = $totalRevenue - $totalCosts;

        // ── Build the context string ─────────────────────────────────
        $topProductsStr = $productRevenue->map(fn($p) => "  - {$p['name']}: UGX " . number_format($p['revenue'], 0) . " ({$p['qty']} units)")->implode("\n");
        $lowStockStr    = $lowStock->map(fn($p) => "  - {$p->name}: {$p->stock} left (reorder at {$p->reorder_level})")->implode("\n") ?: '  None';
        $outStockStr    = $outOfStock->map(fn($p) => "  - {$p->name}")->implode("\n") ?: '  None';
        $trendStr       = implode(', ', $monthlyTrend);

        return <<<CONTEXT
DATE: {$now->toDateString()}

SALES SUMMARY:
- Today: UGX {$this->fmt($salesToday->sum('total_amount'))} ({$salesToday->count()} transactions)
- This week: UGX {$this->fmt($salesThisWeek->sum('total_amount'))} ({$salesThisWeek->count()} transactions)
- This month: UGX {$this->fmt($salesThisMon->sum('total_amount'))} ({$salesThisMon->count()} transactions)
- Last month: UGX {$this->fmt($salesLastMon->sum('total_amount'))} ({$salesLastMon->count()} transactions)
- All-time total sales: UGX {$this->fmt($allSales->sum('total_amount'))} ({$allSales->count()} transactions)

MONTHLY REVENUE TREND (last 6 months):
{$trendStr}

PURCHASES (this month): UGX {$this->fmt($purchThisMon->sum('total_amount'))} ({$purchThisMon->count()} purchase orders)
ESTIMATED GROSS PROFIT (this month): UGX {$this->fmt($grossProfit)}

INVENTORY:
- Total products: {$products->count()}
- Low stock items ({$lowStock->count()}):
{$lowStockStr}
- Out of stock ({$outOfStock->count()}):
{$outStockStr}

TOP 5 PRODUCTS BY ALL-TIME REVENUE:
{$topProductsStr}
CONTEXT;
    }

    private function fmt(float $amount): string
    {
        return number_format($amount, 0);
    }
}
