<?php

// Approximate USD value of one unit of each currency, as of 2026-11-13.
// Used only to rank and badge listings priced in different currencies
// (cheapest row, best value per mg, schema price ranges). Prices shown to
// visitors always stay in the vendor's own currency. Static on purpose:
// refresh these a few times a year, no live FX dependency.
return [
    'as_of' => '2026-11-13',
    'usd_per_unit' => [
        'USD' => 1.0,
        'CAD' => 0.71,
        'GBP' => 1.27,
        'EUR' => 1.08,
        'AUD' => 0.65,
        'NZD' => 0.59,
        'CZK' => 0.043,
        'PLN' => 0.25,
        'RON' => 0.22,
        'HUF' => 0.0027,
        'BGN' => 0.55,
        'SEK' => 0.094,
        'NOK' => 0.091,
        'DKK' => 0.145,
        'CHF' => 1.13,
        'JPY' => 0.0066,
        'SGD' => 0.74,
        'MXN' => 0.051,
        'BRL' => 0.18,
        'AED' => 0.27,
        'INR' => 0.012,
        'KRW' => 0.00072,
        'CNY' => 0.14,
        'HKD' => 0.128,
        'TWD' => 0.031,
        'TRY' => 0.029,
        'ZAR' => 0.055,
        'ILS' => 0.27,
    ],
];
