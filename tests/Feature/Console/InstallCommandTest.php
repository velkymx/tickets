<?php

namespace Tests\Feature\Console;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InstallCommandTest extends TestCase
{
    #[Test]
    public function installer_help_lists_options(): void
    {
        $this->artisan('app:install', ['--help' => true])
            ->assertOk()
            ->expectsOutputToContain('--yes')
            ->expectsOutputToContain('--no-build')
            ->expectsOutputToContain('--no-seed');
    }
}
