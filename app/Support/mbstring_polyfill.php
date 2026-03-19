<?php

declare(strict_types=1);

/**
 * Minimal polyfills for `mb_*` functions used by some mail/markdown dependencies.
 *
 * This allows the app to run even when the `mbstring` PHP extension is not installed.
 * Note: these implementations are byte-based and do not provide full multibyte safety.
 */

if (!function_exists('mb_strlen')) {
    function mb_strlen(string $string, ?string $encoding = null): int
    {
        return strlen($string);
    }
}

if (!function_exists('mb_substr')) {
    function mb_substr(string $string, int $start, ?int $length = null, ?string $encoding = null): string
    {
        return $length === null
            ? substr($string, $start)
            : substr($string, $start, $length);
    }
}

if (!function_exists('mb_strcut')) {
    function mb_strcut(string $string, int $start, ?int $length = null, ?string $encoding = null): string
    {
        return $length === null
            ? substr($string, $start)
            : substr($string, $start, $length);
    }
}

