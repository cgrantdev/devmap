<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Scopes\DemoScope;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Issues a new plugin connection token per brand and prints the
 * plaintext once on this console. Nothing is written to the log.
 */
class RotateConnectionTokens extends Command
{
    protected $signature = 'vendors:rotate-connection-tokens
                            {--brand=* : Brand slug or numeric id. Repeat to rotate several.}
                            {--all : Rotate every brand}
                            {--dry-run : List the brands that would be rotated}';

    protected $description = 'Rotate PeptideMap Connect tokens. Plaintext is printed once, to this console only.';

    public function handle(): int
    {
        $refs = array_values(array_filter((array) $this->option('brand'), fn ($ref) => $ref !== null && $ref !== ''));
        $all = (bool) $this->option('all');

        if ($refs === [] && !$all) {
            $this->error('Pass --brand=<slug-or-id> (repeatable) or --all.');

            return self::FAILURE;
        }

        if ($refs !== [] && $all) {
            $this->error('Use either --brand or --all, not both.');

            return self::FAILURE;
        }

        $brands = $all ? $this->everyBrand() : $this->brandsFor($refs);
        if ($brands === null) {
            return self::FAILURE;
        }

        if ($brands->isEmpty()) {
            $this->warn('No brands matched.');

            return self::SUCCESS;
        }

        $hashed = (bool) config('vendor_plugin.hash_tokens');
        $this->info($hashed
            ? 'Hash mode is on. The database will store sha256(token). The plaintext is printed below and not saved.'
            : 'Hash mode is off. Tokens are stored as plaintext, same as today.');

        if ($this->option('dry-run')) {
            $this->table(
                ['id', 'slug', 'name'],
                $brands->map(fn (Brand $brand) => [$brand->id, $brand->slug, $brand->name])->all()
            );
            $this->line('Dry run: no tokens were changed.');

            return self::SUCCESS;
        }

        $confirmed = $this->confirm(sprintf(
            'Rotate connection tokens for %d brand%s? Existing plugin tokens for those brands will stop working.',
            $brands->count(),
            $brands->count() === 1 ? '' : 's'
        ));
        if (!$confirmed) {
            $this->warn('Aborted. No tokens were changed.');

            return self::SUCCESS;
        }

        foreach ($brands as $brand) {
            $plaintext = $this->issue($brand, $hashed);
            // Console only. Do not pass this string to the logger.
            $this->line(sprintf('brand_id=%d slug=%s token=%s', $brand->id, $brand->slug, $plaintext));
        }

        $this->info('Deliver the tokens above to the vendors. They will not be shown again.');

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $refs
     * @return \Illuminate\Support\Collection<int, Brand>|null
     */
    private function brandsFor(array $refs)
    {
        $brands = collect();
        foreach ($refs as $ref) {
            $brand = $this->findBrand((string) $ref);
            if (!$brand) {
                $this->error("Brand not found: {$ref}");

                return null;
            }
            $brands->push($brand);
        }

        return $brands->unique('id')->values();
    }

    private function everyBrand()
    {
        return Brand::withoutGlobalScope(DemoScope::class)->orderBy('id')->get();
    }

    private function findBrand(string $ref): ?Brand
    {
        $query = Brand::withoutGlobalScope(DemoScope::class);

        return ctype_digit($ref)
            ? $query->find((int) $ref)
            : $query->where('slug', $ref)->first();
    }

    private function issue(Brand $brand, bool $hashed): string
    {
        do {
            $plaintext = 'pmap_'.Str::random(40);
            $stored = $hashed ? hash('sha256', $plaintext) : $plaintext;
            $taken = Brand::withoutGlobalScope(DemoScope::class)
                ->where('connection_token', $stored)
                ->where('id', '!=', $brand->id)
                ->exists();
        } while ($taken);

        $brand->connection_token = $stored;
        $brand->connection_token_issued_at = now();
        $brand->connection_token_used_at = null;
        $brand->save();

        return $plaintext;
    }
}
