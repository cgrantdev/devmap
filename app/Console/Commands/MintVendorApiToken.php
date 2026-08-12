<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\VendorApiToken;
use Illuminate\Console\Command;

class MintVendorApiToken extends Command
{
    protected $signature = 'vendor:api-token
                            {brand : Brand slug or numeric id}
                            {--name= : Human label for this token (defaults to "Push API")}
                            {--revoke : Revoke an existing token by name instead of minting}';

    protected $description = 'Mint (or revoke) a vendor Push API token. Plaintext is printed ONCE.';

    public function handle(): int
    {
        $ref = $this->argument('brand');
        $brand = is_numeric($ref)
            ? Brand::find((int) $ref)
            : Brand::where('slug', $ref)->first();

        if (!$brand) {
            $this->error("Brand not found: {$ref}");
            return self::FAILURE;
        }

        $name = $this->option('name') ?: 'Push API';

        if ($this->option('revoke')) {
            $count = VendorApiToken::where('brand_id', $brand->id)
                ->where('name', $name)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);
            $this->info("Revoked {$count} token(s) named '{$name}' for {$brand->name}.");
            return self::SUCCESS;
        }

        [$token, $plain] = VendorApiToken::mint($brand->id, $name);

        $this->info("Token minted for {$brand->name} (id {$brand->id}).");
        $this->newLine();
        $this->line("  Name:  {$token->name}");
        $this->line("  ID:    {$token->id}");
        $this->newLine();
        $this->warn("  Token: {$plain}");
        $this->newLine();
        $this->line("  This is the ONLY time the plaintext is shown. Copy it now.");

        return self::SUCCESS;
    }
}
