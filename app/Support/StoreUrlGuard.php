<?php

namespace App\Support;

/**
 * Rejects store URLs that point at private, loopback, or link-local
 * addresses, including names that resolve to those addresses.
 */
class StoreUrlGuard
{
    public static function isBlocked(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return true;
        }

        $host = trim($host, '[]');
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return self::addressIsBlocked($host);
        }

        $addresses = self::resolve($host);
        if ($addresses === []) {
            return false;
        }

        foreach ($addresses as $address) {
            if (self::addressIsBlocked($address)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private static function resolve(string $host): array
    {
        $addresses = [];
        $records = @dns_get_record($host, DNS_A + DNS_AAAA);
        if (is_array($records)) {
            foreach ($records as $record) {
                if (!empty($record['ip'])) {
                    $addresses[] = $record['ip'];
                }
                if (!empty($record['ipv6'])) {
                    $addresses[] = $record['ipv6'];
                }
            }
        }

        if ($addresses === []) {
            $v4 = @gethostbynamel($host);
            if (is_array($v4)) {
                $addresses = $v4;
            }
        }

        return $addresses;
    }

    private static function addressIsBlocked(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return true;
        }

        $routable = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
        if ($routable === false) {
            return true;
        }

        // fe80::/10 is link-local. FILTER_FLAG_NO_RES_RANGE does not
        // cover every address in that block on all PHP builds.
        $packed = @inet_pton($ip);
        if ($packed !== false && strlen($packed) === 16 && (ord($packed[0]) === 0xfe) && ((ord($packed[1]) & 0xc0) === 0x80)) {
            return true;
        }

        return false;
    }
}
