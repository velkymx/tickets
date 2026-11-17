<?php

namespace App\Automations\Support;

use App\Automations\Exceptions\SsrfException;

class SsrfGuard
{
    private const BLOCKED_IPS = [
        '169.254.169.254',
        '100.100.100.200',
    ];

    private const SENSITIVE_HEADERS = [
        'authorization',
        'cookie',
        'proxy-authorization',
    ];

    public function validate(string $url): void
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host']) || $parts['host'] === '') {
            throw new SsrfException("Invalid URL: cannot parse host from [{$url}]");
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            throw new SsrfException("Blocked request to [{$url}]: scheme must be http or https");
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new SsrfException("Blocked request to [{$url}]: userinfo is not allowed");
        }

        foreach ($this->resolveIps($parts['host']) as $ip) {
            if ($this->isLoopback($ip) || $this->isPrivate($ip) || $this->isBlocked($ip) || $this->isCgnat($ip)) {
                throw new SsrfException("Blocked request to [{$url}]: resolved to private/reserved address [{$ip}]");
            }
        }
    }

    public function stripSensitiveHeaders(array $headers): array
    {
        return array_filter(
            $headers,
            fn ($key) => ! in_array(strtolower((string) $key), self::SENSITIVE_HEADERS, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    public function sameOrigin(string $from, string $to): bool
    {
        $a = parse_url($from);
        $b = parse_url($to);

        if ($a === false || $b === false) {
            return false;
        }

        return strtolower($a['scheme'] ?? '') === strtolower($b['scheme'] ?? '')
            && strtolower($a['host'] ?? '') === strtolower($b['host'] ?? '')
            && ($a['port'] ?? null) === ($b['port'] ?? null);
    }

    private function resolveIps(string $host): array
    {
        $literal = $this->normalizeLiteral($host);

        if ($literal !== null) {
            return [$literal];
        }

        $ips = [];
        $ipv4 = gethostbyname($host);

        if ($ipv4 !== $host) {
            $ips[] = $ipv4;
        }

        foreach (dns_get_record($host, DNS_AAAA) ?: [] as $record) {
            if (isset($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        if ($ips === []) {
            throw new SsrfException("Invalid URL: host [{$host}] does not resolve");
        }

        return array_values(array_unique($ips));
    }

    private function normalizeLiteral(string $host): ?string
    {
        $candidate = rtrim($host, '.');

        if (str_contains($candidate, '%')) {
            return null;
        }

        if (str_starts_with($candidate, '[') && str_ends_with($candidate, ']')) {
            $candidate = substr($candidate, 1, -1);
        }

        if (str_contains($candidate, ':')) {
            $packed = inet_pton($candidate);

            if ($packed === false) {
                return null;
            }

            $normalized = strtolower(inet_ntop($packed));

            if (str_starts_with($normalized, '::ffff:')) {
                $mapped = substr($normalized, strlen('::ffff:'));

                if ($this->isDecimalQuad($mapped)) {
                    return $mapped;
                }
            }

            return $normalized;
        }

        $dotted = $this->normalizeDecimalQuad($candidate);

        if ($dotted === null) {
            return null;
        }

        return filter_var($dotted, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? $dotted : null;
    }

    private function normalizeDecimalQuad(string $host): ?string
    {
        if (ctype_digit($host)) {
            $long = (int) $host;

            return long2ip($long);
        }

        $parts = explode('.', $host);

        if (count($parts) !== 4) {
            return null;
        }

        $normalized = [];

        foreach ($parts as $part) {
            if ($part === '' || strlen($part) > 10) {
                return null;
            }

            if (preg_match('/^0[xX][0-9a-fA-F]+$/', $part)) {
                $value = hexdec($part);
            } elseif (strlen($part) > 1 && str_starts_with($part, '0')) {
                if (! preg_match('/^0[0-7]+$/', $part)) {
                    return null;
                }
                $value = octdec($part);
            } elseif (ctype_digit($part)) {
                $value = (int) $part;
            } else {
                return null;
            }

            if ($value < 0 || $value > 255) {
                return null;
            }

            $normalized[] = (string) $value;
        }

        return implode('.', $normalized);
    }

    private function isDecimalQuad(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    private function isLoopback(string $ip): bool
    {
        return str_starts_with($ip, '127.') || $ip === '::1' || strtolower($ip) === 'localhost';
    }

    private function isPrivate(string $ip): bool
    {
        return ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    private function isBlocked(string $ip): bool
    {
        return in_array($ip, self::BLOCKED_IPS, true);
    }

    private function isCgnat(string $ip): bool
    {
        $long = ip2long($ip);
        if ($long === false) {
            return false;
        }

        // 100.64.0.0/10
        return ($long & 0xFFC00000) === ip2long('100.64.0.0');
    }
}
