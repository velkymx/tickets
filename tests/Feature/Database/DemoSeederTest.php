<?php

namespace Tests\Feature\Database;

use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class DemoSeederTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function demo_seeder_builds_a_playground_project(): void
    {
        $this->seed(DemoSeeder::class);

        $this->assertDatabaseHas('projects', ['name' => 'Demo Sandbox']);
        $this->assertDatabaseHas('tickets', ['subject' => 'Demo: blocked checkout flow']);
        $this->assertDatabaseHas('notes', ['body' => 'Demo: ship checklist']);
    }

    #[Test]
    public function demo_seeder_is_idempotent(): void
    {
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->assertEquals(1, DB::table('projects')->where('name', 'Demo Sandbox')->count());
        $this->assertEquals(1, DB::table('tickets')->where('subject', 'Demo: blocked checkout flow')->count());
        $this->assertEquals(1, DB::table('notes')->where('body', 'Demo: ship checklist')->count());
    }

    #[Test]
    public function demo_seeder_runs_through_the_documented_command(): void
    {
        $this->artisan('db:seed', ['--class' => 'DemoSeeder'])->assertOk();

        $this->assertDatabaseHas('projects', ['name' => 'Demo Sandbox']);
        $this->assertDatabaseHas('tickets', ['subject' => 'Demo: blocked checkout flow']);
    }
}
