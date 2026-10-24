<?php

namespace Tests\Unit\Policies;

use App\Models\Note;
use App\Models\Ticket;
use App\Models\User;
use App\Policies\NotePolicy;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class NotePolicyTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function notes_resolve_to_note_policy(): void
    {
        $this->assertInstanceOf(NotePolicy::class, Gate::getPolicyFor(Note::class));
    }

    #[Test]
    public function author_may_update_own_note(): void
    {
        $user = User::factory()->create();
        $note = Note::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('update', $note));
    }

    #[Test]
    public function stranger_may_not_update_others_note(): void
    {
        $author = User::factory()->create();
        $stranger = User::factory()->create();
        $note = Note::factory()->create(['user_id' => $author->id]);

        $this->assertFalse($stranger->can('update', $note));
    }

    #[Test]
    public function ticket_owner_may_moderate_notes(): void
    {
        $owner = User::factory()->create();
        $author = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id]);
        $note = Note::factory()->create(['user_id' => $author->id, 'ticket_id' => $ticket->id]);

        foreach (['hide', 'promote', 'pin', 'attach', 'resolve'] as $ability) {
            $this->assertTrue($owner->can($ability, $note), "owner cannot {$ability}");
        }
    }

    #[Test]
    public function stranger_may_not_moderate_notes(): void
    {
        $owner = User::factory()->create();
        $author = User::factory()->create();
        $stranger = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id]);
        $note = Note::factory()->create(['user_id' => $author->id, 'ticket_id' => $ticket->id]);

        foreach (['hide', 'promote', 'pin', 'attach', 'resolve'] as $ability) {
            $this->assertFalse($stranger->can($ability, $note), "stranger can {$ability}");
        }
    }

    #[Test]
    public function admin_may_do_anything(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $note = Note::factory()->create();

        $this->assertTrue($admin->can('update', $note));
        $this->assertTrue($admin->can('resolve', $note));
    }
}
