<?php

namespace Tests\Unit\Models;

use App\Models\Note;
use App\Models\NoteReaction;
use App\Models\Ticket;
use App\Models\User;
use Tests\Traits\SeedsDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NoteReactionTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_has_the_expected_fillable_fields(): void
    {
        $reaction = new NoteReaction;

        $this->assertSame(['note_id', 'user_id', 'emoji'], $reaction->getFillable());
    }

    #[Test]
    public function it_limits_v21_to_the_allowed_emojis(): void
    {
        $this->assertSame(['thumbsup', 'eyes'], NoteReaction::ALLOWED_EMOJIS);
    }

    #[Test]
    public function it_belongs_to_a_note_and_user(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $reaction = NoteReaction::create([
            'note_id' => $note->id,
            'user_id' => $user->id,
            'emoji' => 'thumbsup',
        ]);

        $this->assertTrue($reaction->note->is($note));
        $this->assertTrue($reaction->user->is($user));
    }
}
