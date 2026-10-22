<?php

use App\Content\EducationalContentPublisher;

return [

    /*
    | Pages that render a visible FAQ. On these pages one normalised
    | {question, answer} list feeds both that section and faqPageSchema().
    |
    | Blog slugs are derived from EducationalContentPublisher::catalog()
    | (kind blog) via blogSlugs(). Those faq() pairs are QA-passed, so
    | the list is not copied by hand. Encyclopedia starts empty: the
    | live BPC-157 FAQ is still the old use-guidance copy, and its
    | replacement has not merged. Matching for encyclopedia slugs is
    | case-insensitive, so a later 'bpc-157' entry covers BPC-157.
    |
    | Every other page keeps its existing FAQPage JSON-LD and does not
    | gain a visible FAQ. Stored FAQ rows are not rewritten.
    */
    'faq_render_allowlist' => [
        'blog' => EducationalContentPublisher::blogSlugs(),
        'encyclopedia' => [],
    ],

];
