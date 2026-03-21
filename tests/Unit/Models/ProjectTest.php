<?php

namespace Tests\Unit\Models;

use App\Models\Project;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_correct_fillable_fields(): void
    {
        $project = new Project;
        $fillable = $project->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('description', $fillable);
        $this->assertContains('active', $fillable);
    }

    #[Test]
    public function it_has_timestamps_disabled(): void
    {
        $project = new Project;

        $this->assertFalse($project->timestamps);
    }

    #[Test]
    public function it_casts_active_to_boolean(): void
    {
        $project = Project::factory()->create(['active' => 1]);

        $this->assertIsBool($project->active);
        $this->assertTrue($project->active);
    }

    #[Test]
    public function it_has_many_tickets(): void
    {
        $project = Project::factory()->create();
        $ticket = Ticket::factory()->create(['project_id' => $project->id]);

        $this->assertCount(1, $project->tickets);
        $this->assertTrue($project->tickets->contains($ticket));
    }
}
