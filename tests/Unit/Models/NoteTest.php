<?php

namespace Tests\Unit\Models;

use App\Models\Note;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NoteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_correct_fillable_fields(): void
    {
        $note = new Note;
        $fillable = $note->getFillable();

        $this->assertContains('body', $fillable);
        $this->assertContains('user_id', $fillable);
        $this->assertContains('ticket_id', $fillable);
        $this->assertContains('hours', $fillable);
        $this->assertContains('notetype', $fillable);
        $this->assertContains('hide', $fillable);
    }

    #[Test]
    public function it_casts_hours_to_decimal(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'hours' => '7.50',
        ]);

        $this->assertEquals('7.50', $note->hours);
    }

    #[Test]
    public function it_casts_hide_to_boolean(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'hide' => 1,
        ]);

        $this->assertIsBool($note->hide);
        $this->assertTrue($note->hide);
    }

    #[Test]
    public function it_belongs_to_user(): void
    {
        $user = User::factory()->create(['name' => 'Note Author']);
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $note->user);
        $this->assertEquals('Note Author', $note->user->name);
    }

    #[Test]
    public function it_belongs_to_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'subject' => 'Test Ticket',
        ]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(Ticket::class, $note->ticket);
        $this->assertEquals('Test Ticket', $note->ticket->subject);
    }
}
