<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InstallCommandTest extends TestCase
{
    #[Test]
    public function installer_is_registered_with_options(): void
    {
        $command = Artisan::all()['app:install'];

        $this->assertTrue($command->getDefinition()->hasOption('yes'));
        $this->assertTrue($command->getDefinition()->hasOption('no-build'));
        $this->assertTrue($command->getDefinition()->hasOption('no-seed'));
    }

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
