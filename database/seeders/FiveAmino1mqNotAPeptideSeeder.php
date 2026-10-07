<?php

namespace Database\Seeders;

use App\Models\EducationPost;
use Illuminate\Database\Seeder;

/**
 * Class polish for the live 5-Amino-1MQ encyclopedia row.
 *
 * States that the compound is a small-molecule NNMT inhibitor, not a
 * peptide, and cross-links SLU-PP-332 as a parallel non-peptide research
 * chemical. Replaces subtitle, overview short, overview, and key points.
 *
 * Matches LOWER(slug) = 5-amino-1mq and exactly one category. Does not
 * create a category. Does not overwrite formula, molecular weight, or CAS.
 * Safe to run twice.
 */
class FiveAmino1mqNotAPeptideSeeder extends Seeder
{
    public const SLUG = '5-amino-1mq';

    public const SUBTITLE = 'Small-molecule NNMT inhibitor (not a peptide)';

    public const OVERVIEW_SHORT = '5-Amino-1MQ (5-amino-1-methylquinolinium) is a synthetic small-molecule inhibitor of nicotinamide N-methyltransferase (NNMT). It is a research chemical, not a peptide: it has no amino-acid sequence (live residue count is 0). PeptideMap lists it because metabolic research catalogs often co-list it beside peptides — catalog adjacency is not a chemistry class. Not approved for human use. Educational RUO framing only.';

    public const OVERVIEW_BODY = '5-Amino-1MQ, also called 5-Amino-1-Methylquinolinium, is a cell-permeable small-molecule NNMT inhibitor used in preclinical metabolic research. Unlike peptide research tools, it is a quinolinium small molecule, not a chain of amino acids. Live encyclopedia sequence fields are empty by design (residue count 0).

PubChem lists 5-amino-1-methylquinolinium as CID 950107 (molecular formula C10H11N2+, average mass 159.21, CAS Registry Number among synonyms 685079-15-6). That record is a cation identity check for the core scaffold. Salt forms and commercial labels may differ. This pass does not overwrite the stored formula or molecular weight with that cation row.

Mechanism framing stays at pathway literacy: NNMT methylates nicotinamide and sits at the intersection of NAD+ salvage and methyl-donor metabolism in published preclinical work. That is enzyme-inhibitor pharmacology, not peptide-receptor agonism.

Another non-peptide research chemical commonly co-listed in the same vendor aisles is <a href="/encyclopedia/slu-pp-332">SLU-PP-332</a> — a small-molecule ERR pan-agonist (benzohydrazide; already described on PeptideMap as “not a peptide”). Co-listing reflects metabolic-catalog marketing, not a shared peptide class or shared mechanism.';

    /** @var list<string> */
    public const KEY_POINTS = [
        'Not a peptide — small-molecule quinolinium NNMT inhibitor (no amino-acid sequence)',
        'Preclinical tool for studying NNMT / NAD+-adjacent pathway biology',
        'Catalog co-listing with research peptides is not a peptide chemistry class',
        'Related non-peptide neighbor on PeptideMap: SLU-PP-332 (/encyclopedia/slu-pp-332), an ERR pan-agonist small molecule',
        'Not approved for human use — research use only (RUO)',
        'No dosing or body-composition treatment claims on this page',
    ];

    /** @var list<string> */
    private const FIELDS = [
        'peptide_full_name',
        'description',
        'overview',
        'key_points',
        'molecular_formula',
        'molecular_weight',
        'cas_registry_number',
        'background',
        'faqs',
        'conclusion',
        'references',
    ];

    /** @var array<string, bool> */
    public array $report = [
        'updated' => false,
        'missing_category' => false,
        'missing_post' => false,
        'skipped_slug_collision' => false,
    ];

    public function run(): void
    {
        $match = EncyclopediaCategoryMatch::find(self::SLUG, $this->command, '5-Amino-1MQ class polish');
        $this->report['missing_category'] = $match->missingCategory;
        $this->report['skipped_slug_collision'] = $match->skippedSlugCollision;
        if (! $match->category) {
            return;
        }

        $post = EducationPost::query()
            ->where('product_category_id', $match->category->id)
            ->orderBy('id')
            ->first();
        if (! $post) {
            $this->report['missing_post'] = true;
            $this->command?->warn('5-Amino-1MQ class polish: category exists but has no education post.');

            return;
        }

        $formula = $post->molecular_formula;
        $weight = $post->molecular_weight;
        $cas = $post->cas_registry_number;

        $before = $this->snapshot($post);
        $post->peptide_full_name = self::SUBTITLE;
        $post->description = self::OVERVIEW_SHORT;
        $post->overview = self::OVERVIEW_SHORT."\n\n".self::OVERVIEW_BODY;
        $post->key_points = self::KEY_POINTS;
        $post->molecular_formula = $formula;
        $post->molecular_weight = $weight;
        $post->cas_registry_number = $cas;

        if ($this->snapshot($post) === $before) {
            $this->command?->info('5-Amino-1MQ class polish: already current.');

            return;
        }

        $post->save();
        $this->report['updated'] = true;
        $this->command?->info('5-Amino-1MQ class polish: updated '.$match->category->slug.'.');
    }

    private function snapshot(EducationPost $post): string
    {
        return json_encode($post->only(self::FIELDS), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
