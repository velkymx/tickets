<?php

namespace Tests\Unit\Automations\Support;

use App\Automations\Exceptions\SsrfException;
use App\Automations\Support\SsrfGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SsrfGuardTest extends TestCase
{
    #[Test]
    public function it_allows_a_public_ip(): void
    {
        $this->expectNotToPerformAssertions();

        (new SsrfGuard)->validate('https://93.184.215.14/webhook');
    }

    #[Test]
    public function it_allows_an_uppercase_scheme_and_a_public_ipv6_literal(): void
    {
        $this->expectNotToPerformAssertions();

        (new SsrfGuard)->validate('HTTPS://93.184.215.14/webhook');
        (new SsrfGuard)->validate('https://[2606:4700:4700::1111]/webhook');
    }

    public static function blockedUrls(): array
    {
        return [
            'loopback' => ['http://127.0.0.1/'],
            'localhost' => ['http://localhost/'],
            'private 10/8' => ['http://10.0.0.1/'],
            'private 172.16/12' => ['http://172.16.5.4/'],
            'private 192.168/16' => ['http://192.168.1.1/'],
            'cgnat 100.64/10' => ['http://100.64.0.1/'],
            'aws metadata' => ['http://169.254.169.254/latest/meta-data/'],
            'alibaba metadata' => ['http://100.100.100.200/'],
            'no host' => ['not-a-url'],
            'ftp scheme' => ['ftp://93.184.215.14/file'],
            'file scheme' => ['file:///etc/passwd'],
            'uppercase scheme' => ['GOPHER://93.184.215.14/'],
            'userinfo' => ['https://user:secret@93.184.215.14/'],
            'userinfo without password' => ['https://user@93.184.215.14/'],
            'loopback on a non-default port' => ['http://127.0.0.1:8080/'],
            'decimal loopback' => ['http://2130706433/'],
            'hex loopback' => ['http://0x7f000001/'],
            'octal loopback' => ['http://0177.0.0.1/'],
            'ipv6 loopback' => ['http://[::1]/'],
            'ipv6 expanded loopback' => ['http://[0:0:0:0:0:0:0:1]/'],
            'ipv6 mapped loopback' => ['http://[::ffff:127.0.0.1]/'],
            'ipv6 link local' => ['http://[fe80::1]/'],
            'ipv6 unique local' => ['http://[fd00::1]/'],
            'ipv6 unspecified' => ['http://[::]/'],
        ];
    }

    #[Test]
    #[DataProvider('blockedUrls')]
    public function it_blocks_internal_targets(string $url): void
    {
        $this->expectException(SsrfException::class);

        (new SsrfGuard)->validate($url);
    }
}
