<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Queued job — generates one Claude-authored unique description for
 * one product and writes it to products.generated_description.
 *
 * Why queued: ~2s per API call × 4,500 products = hours. PHP's
 * max_execution_time + nginx proxy timeout kill any inline run past
 * ~30 products. Queue dispatch returns in <1s per job while the
 * database queue worker chews through them in the background.
 *
 * Pairs with GenerateProductDescriptions command which batches the
 * dispatch and returns immediately with "N jobs queued".
 */
class GenerateProductDescriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;
    public int $backoff = 10;

    private const MODEL = 'claude-haiku-4-5-20251001';
    private const MAX_TOKENS = 400;

    public function __construct(public int $productId) {}

    public function handle(): void
    {
        $product = Product::with(['brand', 'category'])->find($this->productId);
        if (!$product) return;
        // Skip if already filled since dispatch — avoids wasted API spend
        // if the command was re-run with overlapping batches.
        if (!empty($product->vendor_description) || !empty($product->generated_description)) return;

        $apiKey = config('services.anthropic.api_key');
        if (!$apiKey) {
            Log::warning('GenerateProductDescriptionJob: ANTHROPIC_API_KEY not set');
            return;
        }

        $brand = $product->brand?->name ?? 'this vendor';
        $compound = $product->category?->name ?? 'the compound';
        $size = $product->size_mg ?: 'unspecified size';
        $priceLine = $product->price > 0
            ? "Priced at \${$product->price}" . ($product->discount_price ? " (sale \${$product->discount_price})" : '')
            : 'Price not listed';
        $type = $product->product_type ?: 'research compound';

        $prompt = "You are writing a 90-130 word product description for a research-peptide comparison site. Rules:

- RESEARCH USE ONLY — never imply human use, dosing, or clinical application
- Factual + distinct voice; no marketing fluff ('premium', 'best-in-class', etc.)
- Mention the compound, the vendor, the size/form, and one specific research-context fact about the compound (mechanism, structure, or research use)
- Each description must read differently from every other one, even for products of the same compound
- No bullet lists, no headers — one flowing paragraph
- Do NOT invent certifications, purity numbers, lab results, or shipping claims
- Do NOT include a call to action or price mention

Product details:
- Name: {$product->name}
- Vendor: {$brand}
- Compound family: {$compound}
- Format: {$type}
- Size: {$size}
- {$priceLine}

Write ONLY the description paragraph — no preamble, no label, no quotes.";

        $resp = Http::timeout(45)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => self::MODEL,
                'max_tokens' => self::MAX_TOKENS,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);

        if (!$resp->successful()) {
            throw new \RuntimeException("Claude HTTP {$resp->status()}: " . mb_substr($resp->body(), 0, 200));
        }

        $text = trim((string) ($resp->json('content.0.text') ?? ''));
        if ($text === '') return;

        $product->forceFill([
            'generated_description' => $text,
            'generated_description_at' => now(),
        ])->save();
    }
}
