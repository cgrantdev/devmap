<?php

namespace App\Support;

/**
 * Country → currency mapping.
 *
 * Vendor prices are stored in whatever currency the vendor's site displays
 * them in — a UK vendor's £45 lives in the DB as 45. Without a symbol next
 * to it, that '45' reads as $45 to a US visitor, which is misleading.
 *
 * This class is the single source of truth for {country name} →
 * {ISO code, display symbol}. Extend `MAP` as new vendor countries land.
 */
class Currency
{
    // Country name (matches locations.name in the DB) → [code, symbol].
    // "Native" currency wins over Euro even when the country is in the EU
    // (e.g. Czechia → CZK not EUR) because vendors price in the currency
    // their own checkout uses.
    private const MAP = [
        'United States'        => ['USD', '$'],
        'Canada'               => ['CAD', 'C$'],
        'United Kingdom'       => ['GBP', '£'],
        'Australia'            => ['AUD', 'A$'],
        'New Zealand'          => ['NZD', 'NZ$'],
        'Germany'              => ['EUR', '€'],
        'France'               => ['EUR', '€'],
        'Netherlands'          => ['EUR', '€'],
        'Ireland'              => ['EUR', '€'],
        'Italy'                => ['EUR', '€'],
        'Spain'                => ['EUR', '€'],
        'Portugal'             => ['EUR', '€'],
        'Belgium'              => ['EUR', '€'],
        'Austria'              => ['EUR', '€'],
        'Finland'              => ['EUR', '€'],
        'Greece'               => ['EUR', '€'],
        'Estonia'              => ['EUR', '€'],
        'Latvia'               => ['EUR', '€'],
        'Lithuania'            => ['EUR', '€'],
        'Slovakia'             => ['EUR', '€'],
        'Slovenia'             => ['EUR', '€'],
        'Luxembourg'           => ['EUR', '€'],
        'Malta'                => ['EUR', '€'],
        'Cyprus'               => ['EUR', '€'],
        'Czechia'              => ['CZK', 'Kč'],
        'Poland'               => ['PLN', 'zł'],
        'Romania'              => ['RON', 'lei'],
        'Hungary'              => ['HUF', 'Ft'],
        'Bulgaria'             => ['BGN', 'лв'],
        'Sweden'               => ['SEK', 'kr'],
        'Norway'               => ['NOK', 'kr'],
        'Denmark'              => ['DKK', 'kr'],
        'Switzerland'          => ['CHF', 'CHF'],
        'Japan'                => ['JPY', '¥'],
        'Singapore'            => ['SGD', 'S$'],
        'Mexico'               => ['MXN', 'Mex$'],
        'Brazil'               => ['BRL', 'R$'],
        'United Arab Emirates' => ['AED', 'AED'],
        'India'                => ['INR', '₹'],
        'South Korea'          => ['KRW', '₩'],
        'China'                => ['CNY', '¥'],
        'Hong Kong'            => ['HKD', 'HK$'],
        'Taiwan'               => ['TWD', 'NT$'],
        'Turkey'               => ['TRY', '₺'],
        'South Africa'         => ['ZAR', 'R'],
        'Israel'               => ['ILS', '₪'],
    ];

    private const DEFAULT = ['USD', '$'];

    /** [code, symbol] for a country name; falls back to USD/$ for unknowns. */
    public static function forCountry(?string $country): array
    {
        if (!$country) return self::DEFAULT;
        return self::MAP[trim($country)] ?? self::DEFAULT;
    }

    public static function codeFor(?string $country): string
    {
        return self::forCountry($country)[0];
    }

    public static function symbolFor(?string $country): string
    {
        return self::forCountry($country)[1];
    }
}
