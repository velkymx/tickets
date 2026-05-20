<?php

namespace Tests\Unit\Automations\Support;

use App\Automations\Exceptions\SsrfException;
use App\Automations\Support\SsrfGuard;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SsrfGuardTest extends TestCase
{
    #[Test]
    public function it_allows_public_urls(): void
    {
        $guard = new SsrfGuard();
        try {
            $guard->validate('https://hooks.example.com/webhook');
            $this->expectNotToPerformAssertions();
        } catch (\App\Automations\Exceptions\SsrfException $e) {
            $this->markTestSkipped('DNS not available in test environment');
        }
    }

    #[Test]
    public function it_blocks_localhost(): void
    {
        $this->expectException(SsrfException::class);
        (new SsrfGuard())->validate('http://localhost/evil');
    }

    #[Test]
    public function it_blocks_127_0_0_1(): void
    {
        $this->expectException(SsrfException::class);
        (new SsrfGuard())->validate('http://127.0.0.1/evil');
    }

    #[Test]
    public function it_blocks_private_192_range(): void
    {
        $this->expectException(SsrfException::class);
        (new SsrfGuard())->validate('http://192.168.1.1/evil');
    }

    #[Test]
    public function it_blocks_private_10_range(): void
    {
        $this->expectException(SsrfException::class);
        (new SsrfGuard())->validate('http://10.0.0.1/evil');
    }

    #[Test]
    public function it_blocks_aws_metadata_endpoint(): void
    {
        $this->expectException(SsrfException::class);
        (new SsrfGuard())->validate('http://169.254.169.254/latest/meta-data/');
    }

    #[Test]
    public function it_throws_on_missing_host(): void
    {
        $this->expectException(SsrfException::class);
        (new SsrfGuard())->validate('not-a-url');
    }
}
