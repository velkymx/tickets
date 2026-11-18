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
    public function batch_form_offers_no_change_first_on_every_select(): void
    {
        $user = User::factory()->create();

        $content = $this->actingAs($user)->get('/tickets')->assertOk()->getContent();

        foreach (['batch_type_id', 'batch_importance_id', 'batch_milestone_id', 'batch_status_id', 'batch_project_id', 'batch_user_id2', 'batch_release_id'] as $selectId) {
            $matched = preg_match('/<select[^>]*id="'.$selectId.'"[^>]*>(.*?)<\/select>/s', $content, $m);
            $this->assertSame(1, $matched, "Select {$selectId} must render.");
            $this->assertMatchesRegularExpression(
                '/^\s*<option value="0"[^>]*>No Change<\/option>/',
                $m[1],
                "Select {$selectId} must lead with a No Change option."
            );
            $this->assertSame(1, substr_count($m[1], '<option value="0"'), "Select {$selectId} must offer No Change exactly once.");
        }
    }

    #[Test]
    public function batch_rejects_a_ticket_list_instead_of_an_id_keyed_map(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $first = Ticket::factory()->create();
        $second = Ticket::factory()->create();

        $response = $this->actingAs($admin)->post('/tickets/batch', [
            'tickets' => [$first->id, $second->id],
        ]);

        $response->assertSessionHasErrors([
            'tickets' => 'Tickets must be keyed by ticket id.',
        ]);
    }

    #[Test]
    public function batch_rejects_a_scalar_tickets_payload(): void
    {
        $admin = User::factory()->create(['admin' => true]);

        $response = $this->actingAs($admin)->post('/tickets/batch', [
            'tickets' => 12,
        ]);

        $response->assertSessionHasErrors('tickets');
    }

    #[Test]
    public function batch_missing_selection_reports_only_the_required_error(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/tickets/batch', []);

        $this->assertSame(
            ['tickets' => ['At least one ticket must be selected.']],
            $response->exception->errors()
        );
    }

    #[Test]
    public function batch_no_change_reports_zero_updated(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $first = Ticket::factory()->create();
        $second = Ticket::factory()->create();

        $response = $this->actingAs($admin)->post('/tickets/batch', [
            'tickets' => [$first->id => '1', $second->id => '1'],
            'type_id' => 0,
            'status_id' => 0,
            'importance_id' => 0,
            'milestone_id' => 0,
            'project_id' => 0,
            'user_id2' => 0,
            'release_id' => 0,
        ]);

        $response->assertRedirect('tickets');
        $response->assertSessionHas('info_message', '0 ticket(s) updated');
    }

    #[Test]
    public function batch_no_change_leaves_every_column_untouched(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $ticket = Ticket::factory()->create();
        $before = $ticket->only(['type_id', 'status_id', 'importance_id', 'milestone_id', 'project_id', 'user_id2']);

        $this->actingAs($admin)->post('/tickets/batch', [
            'tickets' => [$ticket->id => '1'],
            'type_id' => 0,
            'status_id' => 0,
            'importance_id' => 0,
            'milestone_id' => 0,
            'project_id' => 0,
            'user_id2' => 0,
            'release_id' => 0,
        ])->assertSessionHasNoErrors();

        $this->assertSame($before, $ticket->fresh()->only(['type_id', 'status_id', 'importance_id', 'milestone_id', 'project_id', 'user_id2']));
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
