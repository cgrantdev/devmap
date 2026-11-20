<?php

namespace App\Support;

/**
 * Rejects store URLs that point at private, loopback, link-local, CGNAT,
 * reserved or otherwise non-public addresses, including names that
 * resolve to those addresses. resolveAllowed() also returns the vetted
 * IPs so the caller can pin the connection (CURLOPT_RESOLVE) and avoid
 * DNS rebinding between this check and the request.
 */
class StoreUrlGuard
{
    /** Ports a WooCommerce store is realistically served on. */
    public const ALLOWED_PORTS = [80, 443, 8080, 8443];

    /** Extra blocked ranges not reliably covered by FILTER_FLAG_NO_PRIV/RES_RANGE. */
    private const BLOCKED_CIDRS = [
        '0.0.0.0/8', '10.0.0.0/8', '100.64.0.0/10', '127.0.0.0/8', '169.254.0.0/16',
        '172.16.0.0/12', '192.0.0.0/24', '192.0.2.0/24', '192.168.0.0/16', '198.18.0.0/15',
        '198.51.100.0/24', '203.0.113.0/24', '224.0.0.0/4', '240.0.0.0/4',
        '::/128', '::1/128', '64:ff9b::/96', '64:ff9b:1::/48', '100::/64', '2001::/32',
        '2001:db8::/32', 'fc00::/7', 'fe80::/10', 'fec0::/10', 'ff00::/8',
    ];

    public static function isBlocked(string $url): bool
    {
        return self::resolveAllowed($url) === null;
    }

    /**
     * @return array{host: string, port: int, ips: list<string>}|null  null = blocked
     */
    public static function resolveAllowed(string $url): ?array
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $host = strtolower(trim((string) ($parts['host'] ?? ''), '[]'));
        if ($host === '' || str_ends_with($host, '.')) {
            return null;
        }

        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
        if (!in_array($port, self::ALLOWED_PORTS, true)) {
            return null;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return self::addressIsBlocked($host) ? null : ['host' => $host, 'port' => $port, 'ips' => [$host]];
        }

        if (function_exists('idn_to_ascii') && preg_match('/[^\x20-\x7e]/', $host)) {
            $host = (string) idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        }
        if (!preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/', $host)) {
            return null; // single-label names such as "localhost" or "redis" included
        }

        // curl/inet_aton read 2130706433, 0x7f000001, 127.1 and 0177.0.0.1 as
        // IPv4 even though filter_var does not. No real TLD is numeric.
        $labels = explode('.', $host);
        if (preg_match('/^(0x[0-9a-f]*|[0-9]+)$/', (string) end($labels))) {
            return null;
        }

        $ips = self::resolve($host);
        if ($ips === []) {
            return null; // fail closed: the HTTP client might still resolve it
        }

        foreach ($ips as $ip) {
            if (self::addressIsBlocked($ip)) {
                return null;
            }
        }

        return ['host' => $host, 'port' => $port, 'ips' => $ips];
    }

    /**
     * Union of DNS A/AAAA and the system resolver (which honours /etc/hosts).
     *
     * @return list<string>
     */
    private static function resolve(string $host): array
    {
        $addresses = [];
        $records = @dns_get_record($host, DNS_A + DNS_AAAA);
        foreach (is_array($records) ? $records : [] as $record) {
            foreach (['ip', 'ipv6'] as $key) {
                if (!empty($record[$key])) {
                    $addresses[] = $record[$key];
                }
            }
        }

        $v4 = @gethostbynamel($host);
        if (is_array($v4)) {
            array_push($addresses, ...$v4);
        }

        return array_values(array_unique($addresses));
    }

    public static function addressIsBlocked(string $ip): bool
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return true;
        }

        // IPv4-mapped (::ffff:a.b.c.d), IPv4-compatible (::a.b.c.d) and
        // 6to4 (2002:AABB:CCDD::) carry an IPv4 address; check that too.
        if (strlen($packed) === 16) {
            if (str_starts_with($packed, str_repeat("\0", 10)."\xff\xff") || str_starts_with($packed, str_repeat("\0", 12))) {
                return self::addressIsBlocked(inet_ntop(substr($packed, 12, 4)));
            }
            if (str_starts_with($packed, "\x20\x02")) {
                if (self::addressIsBlocked(inet_ntop(substr($packed, 2, 4)))) {
                    return true;
                }
            }
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }

        foreach (self::BLOCKED_CIDRS as $cidr) {
            if (self::inCidr($packed, $cidr)) {
                return true;
            }
        }

        return false;
    }

    private static function inCidr(string $packed, string $cidr): bool
    {
        [$net, $bits] = explode('/', $cidr);
        $netPacked = inet_pton($net);
        if (strlen($netPacked) !== strlen($packed)) {
            return false;
        }

        $bits = (int) $bits;
        $bytes = intdiv($bits, 8);
        if (substr($packed, 0, $bytes) !== substr($netPacked, 0, $bytes)) {
            return false;
        }
        $rem = $bits % 8;
        if ($rem === 0) {
            return true;
        }
        $mask = (0xff << (8 - $rem)) & 0xff;

        return (ord($packed[$bytes]) & $mask) === (ord($netPacked[$bytes]) & $mask);
    }
}
