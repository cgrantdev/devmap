<?php

namespace App\Console\Commands;

use App\Models\Brand;
use Illuminate\Console\Command;

class BrandSlugLookup extends Command
{
    protected $signature = 'brands:slug-lookup {query? : Case-insensitive name/slug search}';
    protected $description = 'Report actual brand slugs so sync commands can be wired to the right one.';

    public function handle(): int
    {
        $q = $this->argument('query');
        $query = Brand::query()->orderBy('id');
        if ($q) {
            $query->where(fn ($qq) =>
                $qq->where('name', 'like', "%{$q}%")->orWhere('slug', 'like', "%{$q}%")
            );
        }
        foreach ($query->get(['id', 'name', 'slug']) as $b) {
            $this->line(sprintf('%4d  %-50s  slug=%s', $b->id, $b->name, $b->slug));
        }
        return self::SUCCESS;
    }
}
