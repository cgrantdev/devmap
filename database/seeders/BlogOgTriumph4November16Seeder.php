<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Support\GuardedFieldWriter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * retatrutide-side-effects-trial-data og_description, 16 November 2026
 * (2026_11_16_230000). One column, blogs.seo_og_description, so the share
 * card names the NEJM knee-OA paper (TRIUMPH-4 + TRIUMPH-1-OA).
 * Meta Optimizer signed off on this wording. seo_description is not changed.
 *
 * Lookup: exactly one blog row by LOWER(TRIM(slug)); otherwise a WARNING.
 * Guard: the column must equal the live value read 16 Nov 2026 10:40 ICT
 * (or already the target). seo_description and seo_og_title must still
 * equal their live values ("column drift: <col>" otherwise). If
 * 2026_11_16_200000 runs first it writes the new catalog value itself and
 * this logs "already current". A second run writes nothing.
 */
class BlogOgTriumph4November16Seeder extends Seeder
{
    public const MIGRATION = '2026_11_16_230000';

    public const SLUG = 'retatrutide-side-effects-trial-data';

    public const FIELD = 'seo_og_description';

    public const OLD = 'Retatrutide adverse events from phase 2 papers, the published TRIUMPH-1 and TRIUMPH-2 papers, and other company-reported readouts. Educational only.';

    public const TARGET = 'Retatrutide adverse events from phase 2, TRIUMPH-1, TRIUMPH-2 and the NEJM knee-OA paper (TRIUMPH-4 + TRIUMPH-1-OA), plus company data. Educational only.';

    /** @var array<string, string> live values read 16 Nov 2026 */
    public const SIBLINGS = [
        'seo_description' => 'Retatrutide adverse events from phase 2 papers, the published TRIUMPH-1 and TRIUMPH-2 papers, and other company-reported readouts. Educational only.',
        'seo_og_title' => 'Retatrutide Side Effects: GI, Heart Rate, Dysesthesia Data',
    ];

    /** @var array<string, string> */
    public array $results = [];

    /** @var list<string> */
    public array $lines = [];

    public function run(): void
    {
        $writer = new GuardedFieldWriter(self::MIGRATION, $this->command);
        $rows = Blog::query()->whereRaw('LOWER(TRIM(slug)) = ?', [self::SLUG])->get();

        if ($rows->count() !== 1) {
            $writer->skip(self::SLUG, self::FIELD, $rows->isEmpty() ? 'blog row missing' : 'duplicate blog slug');
        } else {
            /** @var Blog $blog */
            $blog = $rows->first();
            $current = GuardedFieldWriter::comparable($blog->getAttribute(self::FIELD));
            $drifted = null;
            if ($current === GuardedFieldWriter::comparable(self::OLD)) {
                foreach (self::SIBLINGS as $column => $expected) {
                    if (GuardedFieldWriter::comparable($blog->getAttribute($column)) !== GuardedFieldWriter::comparable($expected)) {
                        $drifted = $column;
                        break;
                    }
                }
            }

            if ($drifted !== null) {
                $writer->skip(self::SLUG, self::FIELD, 'column drift: '.$drifted);
            } else {
                DB::transaction(function () use ($writer, $blog): void {
                    if ($writer->apply($blog, self::SLUG, self::FIELD, self::TARGET, [self::OLD]) === GuardedFieldWriter::UPDATED) {
                        $blog->saveQuietly();
                    }
                });
            }
        }

        $this->results = $writer->results;
        $this->lines = $writer->lines;
    }
}
