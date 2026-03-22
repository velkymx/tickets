<?php

namespace Tests\Unit;

use App\Models\Note;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_note_can_have_a_parent_note()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $parent = Note::create([
            'body' => 'Parent note',
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'message',
        ]);

        $reply = Note::create([
            'body' => 'Reply note',
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'parent_id' => $parent->id,
            'notetype' => 'message',
        ]);

        $this->assertEquals($parent->id, $reply->parent_id);
        $this->assertTrue($reply->parent->is($parent));
    }
}
