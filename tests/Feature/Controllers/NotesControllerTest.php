<?php

namespace Tests\Feature\Controllers;

use App\Models\Note;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotesControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_requires_authentication(): void
    {
        $response = $this->post('/notes/hide/1');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function it_allows_note_author_to_hide(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $note = Note::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $user->id, 'hide' => false]);

        $response = $this->actingAs($user)->post("/notes/hide/{$note->id}");

        $response->assertJson(['message' => 'Note Removed!']);
        $this->assertTrue($note->fresh()->hide);
    }

    #[Test]
    public function it_allows_ticket_creator_to_hide(): void
    {
        $creator = User::factory()->create();
        $noteAuthor = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $creator->id, 'user_id2' => $noteAuthor->id]);
        $note = Note::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $noteAuthor->id, 'hide' => false]);

        $response = $this->actingAs($creator)->post("/notes/hide/{$note->id}");

        $response->assertJson(['message' => 'Note Removed!']);
        $this->assertTrue($note->fresh()->hide);
    }

    #[Test]
    public function it_allows_ticket_assignee_to_hide(): void
    {
        $assignee = User::factory()->create();
        $creator = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $creator->id, 'user_id2' => $assignee->id]);
        $note = Note::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $creator->id, 'hide' => false]);

        $response = $this->actingAs($assignee)->post("/notes/hide/{$note->id}");

        $response->assertJson(['message' => 'Note Removed!']);
        $this->assertTrue($note->fresh()->hide);
    }

    #[Test]
    public function it_denies_unrelated_user(): void
    {
        $creator = User::factory()->create();
        $unrelatedUser = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $creator->id, 'user_id2' => $creator->id]);
        $note = Note::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $creator->id, 'hide' => false]);

        $response = $this->actingAs($unrelatedUser)->post("/notes/hide/{$note->id}");

        $response->assertStatus(403);
    }

    #[Test]
    public function it_returns_404_for_nonexistent_note(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/notes/hide/999');

        $response->assertStatus(404);
    }
}
