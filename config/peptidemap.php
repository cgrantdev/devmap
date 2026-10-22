<?php

use App\Content\EducationalContentPublisher;

return [

    /*
    | Pages that render a visible FAQ. On these pages one normalised
    | {question, answer} list feeds both that section and faqPageSchema().
    |
    | Blog slugs are derived from EducationalContentPublisher::catalog()
    | (kind blog) via blogSlugs(). Those faq() pairs are QA-passed, so
    | the list is not copied by hand.
    | Encyclopedia is bpc-157 plus tb-500, ipamorelin, cjc-1295,
    | aod-9604 and kisspeptin. Those five slugs were added with FAQ
    | audit batch 1 (migration 2026_10_22_155000) after QA PASS.
    | Matching for encyclopedia slugs is case-insensitive.
    |
    | Every other page keeps its existing FAQPage JSON-LD and does not
    | gain a visible FAQ. Stored FAQ rows are not rewritten.
    */
    'faq_render_allowlist' => [
        'blog' => EducationalContentPublisher::blogSlugs(),
        'encyclopedia' => [
            'bpc-157',
            'tb-500',
            'ipamorelin',
            'cjc-1295',
            'aod-9604',
            'kisspeptin',
        ],
    ],

];
