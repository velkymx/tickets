<?php

namespace Tests\Unit\Models;

use App\Models\Mention;
use App\Models\Note;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MentionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_the_expected_fillable_fields(): void
    {
        $mention = new Mention;

        $this->assertSame(['note_id', 'user_id'], $mention->getFillable());
    }

    #[Test]
    public function it_belongs_to_a_note_and_user(): void
    {
        $author = User::factory()->create();
        $mentionedUser = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $author->id,
            'user_id2' => $author->id,
        ]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $author->id,
        ]);

        $mention = Mention::create([
            'note_id' => $note->id,
            'user_id' => $mentionedUser->id,
        ]);

        $this->assertTrue($mention->note->is($note));
        $this->assertTrue($mention->user->is($mentionedUser));
    }
}
