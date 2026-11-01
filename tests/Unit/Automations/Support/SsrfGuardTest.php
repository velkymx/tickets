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
