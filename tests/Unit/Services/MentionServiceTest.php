<?php

namespace Tests\Unit\Services;

use App\Models\Mention;
use App\Models\Note;
use App\Models\Ticket;
use App\Models\TicketUserWatcher;
use App\Models\User;
use App\Services\MentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MentionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MentionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new MentionService;
    }

    #[Test]
    public function it_parses_unique_usernames_from_markdown(): void
    {
        $mentions = $this->service->parseMentions(
            "Please sync with @alice and @bob.\n".
            "Loop @alice in again.\n".
            "Ignore support@example.com."
        );

        $this->assertSame(['alice', 'bob'], $mentions);
    }

    #[Test]
    public function it_creates_mentions_for_non_watchers_only(): void
    {
        $author = User::factory()->create();
        $watcher = User::factory()->create();
        $mentioned = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $author->id,
            'user_id2' => $author->id,
        ]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $author->id,
        ]);

        TicketUserWatcher::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $watcher->id,
        ]);

        $this->service->createMentions($note, [$watcher->id, $mentioned->id, $mentioned->id]);

        $this->assertDatabaseMissing('mentions', [
            'note_id' => $note->id,
            'user_id' => $watcher->id,
        ]);
        $this->assertDatabaseHas('mentions', [
            'note_id' => $note->id,
            'user_id' => $mentioned->id,
        ]);
        $this->assertSame(
            1,
            Mention::query()->where('note_id', $note->id)->where('user_id', $mentioned->id)->count()
        );
    }
}
