<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AgentsGuideSyncTest extends TestCase
{
    #[Test]
    public function guide_names_only_paths_that_exist(): void
    {
        $text = (string) file_get_contents(base_path('AGENTS.md'));

        preg_match_all('/`([^`]+)`/', $text, $matches);

        $missing = [];

        foreach (array_unique($matches[1]) as $token) {
            if (! preg_match('#^(app|resources|database|tests|docs)/[A-Za-z0-9_./-]+$#', $token)
                && ! in_array($token, ['llms.txt'], true)) {
                continue;
            }

            if (! file_exists(base_path($token))) {
                $missing[] = $token;
            }
        }

        $this->assertSame([], $missing);
    }

    #[Test]
    public function guide_names_only_commands_that_resolve(): void
    {
        $text = (string) file_get_contents(base_path('AGENTS.md'));

        preg_match_all('/php artisan ([a-z0-9:_-]+)/', $text, $matches);

        $this->assertNotEmpty($matches[1]);

        foreach (array_unique($matches[1]) as $command) {
            $this->assertArrayHasKey($command, Artisan::all(), "AGENTS.md documents php artisan {$command}, which is not registered");
        }
    }
}
