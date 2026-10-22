<?php

namespace App\Console\Commands;

use App\Models\Brand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI gap-filler for brand /brand/{slug} pages. Same pattern as
 * products:generate-descriptions — Haiku 4.5, per-vendor prompt
 * built from the data we already have (name, location, product
 * count, verified badges, usps), writes to
 * vendor_settings.generated_description.
 *
 * Frontend precedence should be: vendor-authored description ->
 * generated_description -> nothing. Author's intent always wins.
 */
class GenerateBrandDescriptions extends Command
{
    protected $signature = 'brands:generate-descriptions
                            {--limit= : Cap number of brands}
                            {--brand= : Only process one brand slug}
                            {--force : Regenerate even if generated_description already set}
                            {--dry-run : Build prompts + skip API calls}';

    protected $description = 'Fill vendor "about" copy gaps with Claude-generated unique paragraphs.';

    private const MODEL = 'claude-haiku-4-5-20251001';
    private const MAX_TOKENS = 500;

    public function handle(): int
    {
        $apiKey = config('services.anthropic.api_key');
        if (!$apiKey && !$this->option('dry-run')) {
            $this->error('ANTHROPIC_API_KEY not set.');
            return self::FAILURE;
        }

        $query = Brand::query()
            ->with(['vendorSetting', 'vendorSetting.location'])
            ->where('is_active', true)
            ->whereHas('vendorSetting');

        if (!$this->option('force')) {
            $query->whereHas('vendorSetting', fn ($q) => $q->whereNull('generated_description'));
        }
        if ($slug = $this->option('brand')) {
            $query->where('slug', $slug);
        }
        if ($limit = (int) $this->option('limit')) {
            $query->limit($limit);
        }

        $total = $query->count();
        if ($total === 0) {
            $this->info('No brands to generate for.');
            return self::SUCCESS;
        }

        $this->info("Generating descriptions for {$total} brand(s)…");

        $ok = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($query->get() as $brand) {
            $prompt = $this->buildPrompt($brand);
            if ($this->option('dry-run')) {
                $this->line("  → {$brand->id} ({$brand->name}) — prompt built (" . mb_strlen($prompt) . " chars)");
                $ok++;
                continue;
            }

            try {
                $text = $this->callClaude($apiKey, $prompt);
                if (!$text) {
                    $skipped++;
                    $this->warn("  · {$brand->id} — empty response");
                    continue;
                }
                $brand->vendorSetting->forceFill([
                    'generated_description' => $text,
                    'generated_description_at' => now(),
                ])->save();
                $ok++;
                $this->line("  → {$brand->id} ({$brand->name}) — " . mb_substr($text, 0, 60) . '…');
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('brands:generate-descriptions error', [
                    'id' => $brand->id, 'err' => $e->getMessage(),
                ]);
                $this->warn("  ✗ {$brand->id} — {$e->getMessage()}");
            }

            usleep(100_000);
        }

        $this->newLine();
        $this->info("Done. {$ok} generated, {$skipped} skipped, {$failed} failed.");
        return self::SUCCESS;
    }

    private function buildPrompt(Brand $brand): string
    {
        $vs = $brand->vendorSetting;
        $location = $vs?->location?->name ?: 'unspecified region';
        $productCount = $brand->products()
            ->where('hidden', false)
            ->where('status', 'active')
            ->count();
        $usps = is_array($vs?->usps) ? implode(', ', array_slice($vs->usps, 0, 6)) : '';
        $founded = $vs?->founded_year ? "Established {$vs->founded_year}" : '';

        // Collect verified badges from certification claims (approved only).
        $badges = \App\Models\VendorCertificationClaim::where('brand_id', $brand->id)
            ->where('status', 'approved')
            ->get()
            ->map(fn ($c) => $c->label())
            ->implode(', ');

        return "You are writing a 110-150 word vendor description for a research-peptide comparison directory. Rules:

- RESEARCH USE ONLY — the vendor supplies research compounds, never imply human use or clinical application
- Factual + distinct voice; no marketing fluff ('premier', 'leading', 'trusted', 'premium')
- Mention the vendor name, their location, one concrete catalog fact (count or category focus), and one differentiator from the data below if any
- Each vendor's description must read differently from every other vendor's — vary structure, sentence order, lead
- No bullet lists, no headers — two flowing paragraphs maximum
- Do NOT invent certifications, review scores, shipping claims, or compound-specific facts not in the data below
- Do NOT include a call to action, pricing, or 'shop now' language
- Do NOT compare to competitors or make superlative claims

Vendor details:
- Name: {$brand->name}
- Location: {$location}
- Active catalog size: {$productCount} products
- {$founded}
- Verified badges: " . ($badges ?: 'none listed') . "
- USPs they chose at onboarding: " . ($usps ?: 'none listed') . "

Write ONLY the paragraph(s) — no preamble, no label, no quotes.";
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
