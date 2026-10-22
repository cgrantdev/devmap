<?php

return [

    /*
    | Encyclopedia slugs that may render a visible FAQ and FAQPage
    | JSON-LD. Matching is case-insensitive. Every other encyclopedia
    | entry keeps its stored faqs column and emits neither a section
    | nor a schema node.
    */
    'encyclopedia_faq_allowlist' => [
        'bpc-157',
    ],

];
