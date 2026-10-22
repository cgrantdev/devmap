<?php

use App\Content\EducationalContentPublisher;

return [

    /*
    | Pages that render a visible FAQ. On these pages one normalised
    | {question, answer} list feeds both that section and faqPageSchema().
    |
    | Blog slugs are derived from EducationalContentPublisher::catalog()
    | (kind blog) via blogSlugs(), excluding the FAQ render hold. Those
    | faq() pairs are QA-passed, so the list is not copied by hand.
    | Encyclopedia is bpc-157 now that the research-use FAQ replacement
    | is on main. Matching for encyclopedia slugs is case-insensitive.
    |
    | Every other page keeps its existing FAQPage JSON-LD and does not
    | gain a visible FAQ. Stored FAQ rows are not rewritten.
    */
    'faq_render_allowlist' => [
        'blog' => EducationalContentPublisher::blogSlugs(),
        'encyclopedia' => [
            'bpc-157',
        ],
    ],

];
