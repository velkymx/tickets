<?php

namespace App\Automations\Support;

use App\Automations\Exceptions\SsrfException;

class SsrfGuard
{
    private const BLOCKED_IPS = [
        '169.254.169.254',
        '100.100.100.200',
    ];

    public function validate(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! $host) {
            throw new SsrfException("Invalid URL: cannot parse host from [{$url}]");
        }

        $ip = gethostbyname($host);

        if ($this->isLoopback($ip) || $this->isPrivate($ip) || $this->isBlocked($ip) || $this->isCgnat($ip)) {
            throw new SsrfException("Blocked request to [{$url}]: resolved to private/reserved address [{$ip}]");
        }
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
