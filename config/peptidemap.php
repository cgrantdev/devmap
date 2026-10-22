<?php

/*
 * Visible FAQ slugs. FAQPage JSON-LD stays on every page; the on-page
 * section renders only for slugs listed here.
 *
 * Page Author PR #58 adds this same key and ships encyclopedia as an
 * empty list, plus a blog list. The blog key is omitted here so a merge
 * does not replace that catalog. Add 'bpc-157' to #58's encyclopedia
 * list if that PR lands first.
 */

return [
    'faq_render_allowlist' => [
        'encyclopedia' => [
            'bpc-157',
        ],
    ],
];
