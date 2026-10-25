<?php

namespace Tests\Feature\Tickets;

use App\Models\Note;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class BatchUpdateTest extends TestCase
{
    use SeedsDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    #[Test]
    public function no_change_defaults_pass_validation(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $ticket = Ticket::factory()->create();

        $response = $this->actingAs($admin)->post('/tickets/batch', [
            'tickets' => [$ticket->id => '1'],
            'type_id' => 0,
            'status_id' => 0,
            'importance_id' => 0,
            'milestone_id' => 0,
            'project_id' => 0,
            'user_id2' => 0,
            'release_id' => 0,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('tickets');
    }

    #[Test]
    public function batch_update_writes_a_changelog_entry(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $ticket = Ticket::factory()->create();
        $newStatus = Status::factory()->create(['name' => 'Escalated']);

        $this->actingAs($admin)->post('/tickets/batch', [
            'tickets' => [$ticket->id => '1'],
            'type_id' => 0,
            'status_id' => $newStatus->id,
            'importance_id' => 0,
            'milestone_id' => 0,
            'project_id' => 0,
            'user_id2' => 0,
            'release_id' => 0,
        ])->assertSessionHasNoErrors();

        $this->assertSame($newStatus->id, $ticket->fresh()->status_id);

        $changelog = Note::where('ticket_id', $ticket->id)
            ->where('notetype', 'changelog')
            ->first();

        $this->assertNotNull($changelog);
        $this->assertStringContainsString('Status changed to Escalated', $changelog->body_markdown);
    }
}
