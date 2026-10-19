<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI gap-filler for the vendor-description scraper (Colin 10/20). For
 * every product that doesn't already have a vendor_description OR a
 * previous generated_description, call Claude Haiku with product
 * context and write a research-use-only description to
 * generated_description.
 *
 * Pricing math: Haiku 4.5 is dirt cheap for this shape. ~400 output
 * tokens × ~4,500 products ≈ $15-30 for the whole catalog.
 *
 * Frontend precedence (set in ProductsController):
 *   vendor_description || generated_description || description
 */
class GenerateProductDescriptions extends Command
{
    protected $signature = 'products:generate-descriptions
                            {--limit= : Cap number of products}
                            {--brand= : Only process one brand slug}
                            {--force : Regenerate even if generated_description already set}
                            {--dry-run : Build prompts + skip API calls}';

    protected $description = 'Fill vendor_description gaps with Claude-generated unique copy.';

    private const MODEL = 'claude-haiku-4-5-20251001';
    private const MAX_TOKENS = 400;

    public function handle(): int
    {
        $apiKey = config('services.anthropic.api_key');
        if (!$apiKey && !$this->option('dry-run')) {
            $this->error('ANTHROPIC_API_KEY not set (reads env ANTHROPIC_API_KEY).');
            return self::FAILURE;
        }

        $query = Product::query()
            ->with(['brand', 'category'])
            ->where('hidden', false)
            ->where('status', 'active')
            ->whereNull('vendor_description');

        if (!$this->option('force')) {
            $query->whereNull('generated_description');
        }
        if ($slug = $this->option('brand')) {
            $query->whereHas('brand', fn ($q) => $q->where('slug', $slug));
        }
        if ($limit = (int) $this->option('limit')) {
            $query->limit($limit);
        }

        $total = $query->count();
        if ($total === 0) {
            $this->info('No products to generate for.');
            return self::SUCCESS;
        }

        $this->info("Generating descriptions for {$total} product(s)…");

        $ok = 0;
        $failed = 0;
        $skipped = 0;

        $query->chunk(50, function ($chunk) use (&$ok, &$failed, &$skipped, $apiKey) {
            foreach ($chunk as $product) {
                $prompt = $this->buildPrompt($product);
                if ($this->option('dry-run')) {
                    $this->line("  → {$product->id} ({$product->name}) — prompt built (" . mb_strlen($prompt) . " chars)");
                    $ok++;
                    continue;
                }

                try {
                    $text = $this->callClaude($apiKey, $prompt);
                    if (!$text) {
                        $skipped++;
                        $this->warn("  · {$product->id} — empty response");
                        continue;
                    }
                    $product->forceFill([
                        'generated_description' => $text,
                        'generated_description_at' => now(),
                    ])->save();
                    $ok++;
                    $this->line("  → {$product->id} ({$product->name}) — " . mb_substr($text, 0, 60) . '…');
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('products:generate-descriptions error', [
                        'id' => $product->id, 'err' => $e->getMessage(),
                    ]);
                    $this->warn("  ✗ {$product->id} — {$e->getMessage()}");
                }

                // Haiku rate limit on paid tier is well above 50/sec;
                // 100ms pacing keeps us polite without wasting time.
                usleep(100_000);
            }
        });

        $this->newLine();
        $this->info("Done. {$ok} generated, {$skipped} skipped, {$failed} failed.");
        return self::SUCCESS;
    }

    private function buildPrompt(Product $product): string
    {
        $brand = $product->brand?->name ?? 'this vendor';
        $compound = $product->category?->name ?? 'the compound';
        $size = $product->size_mg ?: 'unspecified size';
        $priceLine = $product->price > 0
            ? "Priced at \${$product->price}" . ($product->discount_price ? " (sale \${$product->discount_price})" : '')
            : 'Price not listed';
        $type = $product->product_type ?: 'research compound';

        return "You are writing a 90-130 word product description for a research-peptide comparison site. Rules:

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
    }

    private function callClaude(string $apiKey, string $prompt): ?string
    {
        $resp = Http::timeout(30)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => self::MODEL,
                'max_tokens' => self::MAX_TOKENS,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if (!$resp->successful()) {
            throw new \RuntimeException("HTTP {$resp->status()}: " . mb_substr($resp->body(), 0, 200));
        }
        $data = $resp->json();
        $text = $data['content'][0]['text'] ?? null;
        return $text ? trim($text) : null;
    }
}
