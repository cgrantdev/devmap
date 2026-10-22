<?php

namespace App\Support;

/**
 * Neutral title and meta for a product page.
 *
 * app.blade.php does not append the site name when the stored title
 * already contains it, so the stored title is the full <title>.
 * The rendered title stays within 60 characters. The meta stays within
 * 155 and does not include a price, a coupon percentage, or a buy,
 * best, or cheapest claim.
 */
class ProductPageSeo
{
    public const TITLE_LIMIT = 60;

    public const DESCRIPTION_LIMIT = 155;

    public const SITE_SUFFIX = ComparePageSeo::SITE_SUFFIX;

    /**
     * Appended to a short own-copy meta. Includes the leading space.
     */
    public const PUBLISHER_TAIL = ' RUO catalog data only; Peptidemap sells nothing.';

    public const SHORT_META = 120;

    private const TRIM_CHARS = " \t-/+&,|·";

    private const CLAIM_PATTERN = '/\b(?:wound healing|tissue repair|healing|treatments?|therap(?:y|ies)|cures?|weight loss|fat loss|anti-aging|anti aging|boost(?:ing|s|ed)?|dosing)\b/i';

    /**
     * @param  string|null  $rawName  The imported product name, used to keep a CJC-1295 "no DAC" variant that display_name drops.
     */
    public static function title(string $product, ?string $vendor = null, ?string $rawName = null): string
    {
        $product = self::normalizeProductName(self::label($product, 'Product'), $rawName);
        $vendor = trim((string) $vendor);
        $shortVendor = self::withoutLegalSuffix($vendor);

        $candidates = [];
        if ($vendor !== '') {
            $candidates[] = "{$product} — {$vendor} Listing";
            if ($shortVendor !== '' && $shortVendor !== $vendor) {
                $candidates[] = "{$product} — {$shortVendor} Listing";
            }
            $withoutListing = $shortVendor !== '' ? $shortVendor : $vendor;
            $candidates[] = "{$product} — {$withoutListing}";
            if ($withoutListing !== $vendor) {
                $candidates[] = "{$product} — {$vendor}";
            }
        }
        $candidates[] = "{$product} Listing";
        $candidates[] = $product;

        foreach ($candidates as $candidate) {
            if (self::chars($candidate.self::SITE_SUFFIX) <= self::TITLE_LIMIT) {
                return $candidate.self::SITE_SUFFIX;
            }
        }

        return self::shortenTo($product, self::TITLE_LIMIT - self::chars(self::SITE_SUFFIX)).self::SITE_SUFFIX;
    }

    /**
     * Own-copy meta. Vendor text stays the source. Claim language is
     * replaced with a neutral identity line, short lines gain the
     * publisher tail, and nothing is cut mid-sentence with an ellipsis.
     */
    public static function fromOwnCopy(?string $source, string $product, ?string $vendor = null, ?string $class = null, int $vendorCount = 0): string
    {
        $product = self::normalizeProductName(self::label($product, 'Product'));
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $source)) ?? '');
        if ($plain === '' || self::chars($plain) < 40) {
            return self::description($product, $vendor, $vendorCount);
        }
        if (self::hasClaim($plain)) {
            return self::neutralIdentity($product, $vendor, $class ?: self::extractClass($plain));
        }

        $snippet = self::clipComplete($plain, self::DESCRIPTION_LIMIT);
        if ($snippet === null || self::hasClaim($snippet)) {
            return self::neutralIdentity($product, $vendor, $class ?: self::extractClass($plain));
        }

        return self::withPublisherTail($snippet);
    }

    public static function neutralIdentity(string $product, ?string $vendor = null, ?string $class = null): string
    {
        $product = self::normalizeProductName(self::label($product, 'Product'));
        [$compound, $size] = self::splitSize($product);
        $named = $size !== null ? "{$compound} ({$size})" : $compound;
        $vendor = trim((string) $vendor);
        $class = trim((string) $class);
        if ($class !== '' && self::hasClaim($class)) {
            $class = '';
        }

        $candidates = [];
        if ($vendor !== '') {
            if ($class !== '') {
                $candidates[] = "{$vendor} lists {$named}, a {$class}, for research use only (RUO). Compare listings; Peptidemap sells nothing.";
            }
            $candidates[] = "{$vendor} lists {$named} for research use only (RUO). Compare listings; Peptidemap sells nothing.";
            $candidates[] = "{$vendor} lists {$named} for research use only (RUO). Peptidemap sells nothing.";
            $shortVendor = self::withoutLegalSuffix($vendor);
            if ($shortVendor !== '' && $shortVendor !== $vendor) {
                $candidates[] = "{$shortVendor} lists {$named} for research use only (RUO). Peptidemap sells nothing.";
            }
        }
        $candidates[] = "{$named} is listed for research use only (RUO). Compare listings; Peptidemap sells nothing.";
        $candidates[] = "{$named} is listed for research use only (RUO). Peptidemap sells nothing.";
        $candidates[] = 'Research-use-only (RUO) catalog listing. Compare listings; Peptidemap sells nothing.';

        foreach ($candidates as $candidate) {
            if (self::chars($candidate) <= self::DESCRIPTION_LIMIT && ! self::hasClaim($candidate)) {
                return $candidate;
            }
        }

        return 'Research-use-only (RUO) catalog listing. Compare listings; Peptidemap sells nothing.';
    }

    /**
     * Schema.org description. Drops claim sentences, "verified", and
     * any claim that certificates or copy are published on this site.
     */
    public static function schemaDescription(?string $source, string $product, ?string $vendor = null, ?string $class = null): ?string
    {
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $source)) ?? '');
        if ($plain === '') {
            return null;
        }

        $parts = preg_split('/(?<=[.!?])\s+|\s*;\s+/u', $plain) ?: [];
        $kept = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || self::hasClaim($part) || preg_match('/\bverified\b/i', $part) || preg_match('/\bthis site\b/i', $part)) {
                continue;
            }
            $kept[] = self::endSentence($part);
        }

        if ($kept === []) {
            return self::neutralIdentity($product, $vendor, $class);
        }

        return trim(implode(' ', $kept));
    }

    public static function normalizeProductName(string $name, ?string $rawName = null): string
    {
        $name = trim($name);
        if ($name === '') {
            return 'Product';
        }

        $name = preg_replace('/\[\s*(\d+(?:\.\d+)?)\s*(mg|mcg|iu)\s*\]/iu', '($1$2)', $name) ?? $name;
        $name = preg_replace('/\(\s*(\d+(?:\.\d+)?)\s+(mg|mcg|iu)\s*\)/iu', '($1$2)', $name) ?? $name;
        $name = preg_replace('/(?<!\()\s+(\d+(?:\.\d+)?)\s*(mg|mcg)\b/iu', ' ($1$2)', $name) ?? $name;
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
        $name = preg_replace_callback('/\((\d+(?:\.\d+)?)(mg|mcg|iu)\)/iu', function (array $match): string {
            $unit = strtolower($match[2]) === 'iu' ? 'IU' : strtolower($match[2]);

            return '('.$match[1].$unit.')';
        }, $name) ?? $name;

        $name = preg_replace_callback('/\b([A-Za-z0-9]+(?:-[A-Za-z0-9]+)+)\b/u', function (array $match): string {
            return self::recaseToken($match[0]);
        }, $name) ?? $name;
        $name = preg_replace_callback('/\b([A-Z]{4,})\b/u', function (array $match): string {
            if (count(array_unique(str_split($match[1]))) < 2) {
                return $match[1];
            }

            return mb_convert_case(mb_strtolower($match[1]), MB_CASE_TITLE, 'UTF-8');
        }, $name) ?? $name;

        $raw = trim((string) $rawName);
        if ($raw !== ''
            && preg_match('/\b(?:no[\s\-]*dac|without\s+dac)\b/i', $raw)
            && preg_match('/CJC-1295/i', $name)
            && ! preg_match('/\bno\s*dac\b/i', $name)
        ) {
            $name = preg_replace('/CJC-1295/i', 'CJC-1295 no DAC', $name, 1) ?? $name;
        }

        return trim($name);
    }

    public static function hasClaim(string $text): bool
    {
        return (bool) preg_match(self::CLAIM_PATTERN, $text);
    }

    public static function description(string $product, ?string $vendor = null, int $vendorCount = 0): string
    {
        $product = self::label($product, 'This product');
        $vendor = trim((string) $vendor);
        $count = max(0, $vendorCount);
        $from = $vendor !== '' ? " from {$vendor}" : '';
        $candidates = [];

        if ($count > 0) {
            $word = $count === 1 ? 'vendor' : 'vendors';
            $candidates[] = "{$product}{$from}. Compare listings from {$count} {$word}. Catalog data only: RUO, and Peptidemap sells nothing.";
        }

        $candidates[] = "{$product}{$from}. Compare research peptide listings. Catalog data only: RUO, and Peptidemap sells nothing.";
        $candidates[] = "Compare {$product} listings. Catalog data only: RUO, and Peptidemap sells nothing.";
        $candidates[] = "Compare {$product} listings. RUO. Peptidemap sells nothing.";

        foreach ($candidates as $candidate) {
            if (self::chars($candidate) <= self::DESCRIPTION_LIMIT) {
                return $candidate;
            }
        }

        $prefix = 'Compare ';
        $tail = ' listings. RUO. Peptidemap sells nothing.';
        $budget = self::DESCRIPTION_LIMIT - self::chars($prefix.$tail);

        return $prefix.self::shortenTo($product, max(1, $budget)).$tail;
    }

    private static function label(string $value, string $fallback): string
    {
        $value = trim($value);

        return $value !== '' ? $value : $fallback;
    }

    private static function shortenTo(string $value, int $budget): string
    {
        if ($budget < 1) {
            return '…';
        }
        if (self::chars($value) <= $budget) {
            return $value;
        }

        $ellipsis = '…';
        $keep = max(1, $budget - self::chars($ellipsis));
        $slice = self::trimSeparators(mb_substr($value, 0, $keep));
        $space = mb_strrpos($slice, ' ');
        if ($space !== false && $space >= (int) floor($keep / 2)) {
            $slice = self::trimSeparators(mb_substr($slice, 0, $space));
        }
        if ($slice === '') {
            $slice = self::trimSeparators(mb_substr($value, 0, $keep));
        }
        if ($slice === '') {
            $slice = mb_substr($value, 0, $keep);
        }

        return $slice.$ellipsis;
    }

    private static function trimSeparators(string $value): string
    {
        return rtrim($value, self::TRIM_CHARS);
    }

    private static function chars(string $value): int
    {
        return mb_strlen($value);
    }

    private static function withPublisherTail(string $meta): string
    {
        if (self::chars($meta) >= self::SHORT_META || str_contains($meta, 'Peptidemap sells nothing')) {
            return $meta;
        }

        $withTail = $meta.self::PUBLISHER_TAIL;
        if (self::chars($withTail) <= self::DESCRIPTION_LIMIT) {
            return $withTail;
        }

        return $meta;
    }

    private static function clipComplete(string $text, int $limit): ?string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        if ($text === '') {
            return null;
        }
        if (self::chars($text) <= $limit) {
            $ended = self::endSentence($text);

            return self::chars($ended) <= $limit ? $ended : null;
        }

        $sentences = preg_split('/(?<=[.!?])\s+/u', $text) ?: [];
        $built = '';
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if ($sentence === '') {
                continue;
            }
            $candidate = $built === '' ? $sentence : $built.' '.$sentence;
            if (self::chars($candidate) <= $limit && preg_match('/[.!?]$/u', $sentence)) {
                $built = $candidate;
                continue;
            }
            break;
        }
        if ($built !== '') {
            return $built;
        }

        $window = mb_substr($text, 0, $limit);
        $cut = 0;
        foreach ([';', ':', '—', ','] as $separator) {
            $pos = mb_strrpos($window, $separator);
            if ($pos !== false && $pos > $cut) {
                $cut = $pos;
            }
        }
        if ($cut < 40) {
            return null;
        }

        $clause = self::endSentence(rtrim(mb_substr($text, 0, $cut), " \t,;:—-"));

        return self::chars($clause) <= $limit ? $clause : null;
    }

    private static function endSentence(string $text): string
    {
        $text = rtrim($text, " \t");
        if ($text === '') {
            return $text;
        }
        if (! preg_match('/[.!?]$/u', $text)) {
            $text .= '.';
        }

        return $text;
    }

    private static function extractClass(string $text): ?string
    {
        if (! preg_match('/\b(?:a|an)\s+([^,.;]{3,70}?)(?=\s+studied\b|\s+intended\b|\s+supplied\b|\s+for\b|,|\.|$)/iu', $text, $match)) {
            return null;
        }
        $class = trim($match[1]);
        if ($class === '' || self::hasClaim($class) || self::chars($class) > 50) {
            return null;
        }

        return $class;
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private static function splitSize(string $product): array
    {
        if (preg_match('/^(.*?)\s*\(((?:\d+(?:\.\d+)?)(?:mg|mcg|IU))\)\s*$/u', $product, $match)) {
            $compound = trim($match[1]);

            return [$compound !== '' ? $compound : $product, $match[2]];
        }

        return [$product, null];
    }

    private static function withoutLegalSuffix(string $vendor): string
    {
        $short = preg_replace(
            '/(?:,|\s)+(pty\.?\s*ltd\.?|pte\.?\s*ltd\.?|pvt\.?\s*ltd\.?|l\.l\.c\.|llc|inc\.?|ltd\.?|limited|corp\.?|corporation|gmbh|plc|co\.?)\s*$/iu',
            '',
            $vendor
        );

        return trim((string) $short, " \t,.");
    }

    private static function recaseToken(string $token): string
    {
        $key = mb_strtoupper($token);
        $known = [
            'BPC-157' => 'BPC-157',
            'TB-500' => 'TB-500',
            'GHK-CU' => 'GHK-Cu',
            'MOTS-C' => 'MOTS-c',
            'CJC-1295' => 'CJC-1295',
            'AOD-9604' => 'AOD-9604',
            'GLP-2TZ' => 'GLP-2TZ',
            'GLP1-S' => 'GLP1-S',
            'GLP2-T' => 'GLP2-T',
            'GLP3-R' => 'GLP3-R',
            '5-AMINO-1MQ' => '5-Amino-1MQ',
            'NAD+' => 'NAD+',
        ];
        if (isset($known[$key])) {
            return $known[$key];
        }
        if (preg_match('/^GLP/i', $token)) {
            return $key;
        }

        $letters = preg_replace('/[^A-Za-z]/', '', $token) ?? '';
        if ($letters === '' || $letters !== strtoupper($letters)) {
            return $token;
        }

        $parts = [];
        foreach (explode('-', $token) as $part) {
            if (preg_match('/\d/', $part) || mb_strlen($part) <= 3) {
                $parts[] = preg_match('/\d/', $part) ? $part : mb_strtoupper($part);
                continue;
            }
            $parts[] = mb_convert_case(mb_strtolower($part), MB_CASE_TITLE, 'UTF-8');
        }

        return implode('-', $parts);
    }
}
