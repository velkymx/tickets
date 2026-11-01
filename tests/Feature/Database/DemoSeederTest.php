<?php

namespace Tests\Feature\Database;

use App\Models\Note;
use App\Models\User;
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
    public function demo_seeder_adds_decision_requests_at_each_urgency(): void
    {
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $demo = User::where('email', 'demo@ajbapps.com')->firstOrFail();
        $waiting = Note::awaitingDecisionFrom($demo->id)->get();

        $this->assertCount(3, $waiting);
        $this->assertEqualsCanonicalizing(['info', 'warning', 'danger'], $waiting->map->decisionUrgency()->all());
        $this->assertSame(1, Note::where('notetype', 'decision_needed')->where('resolved', true)->count());
    }
}
