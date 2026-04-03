<?php

namespace Tests\Unit\Services;

use App\Models\Mention;
use App\Models\Note;
use App\Models\Ticket;
use App\Models\TicketUserWatcher;
use App\Models\User;
use App\Services\MentionService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class MentionServiceTest extends TestCase
{
    use SeedsDatabase;

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
            "Please sync with @[Alice (PM)] and @[Bob (Dev)].\n".
            "Loop @[Alice (PM)] in again.\n".
            'Ignore support@example.com.'
        );

        $this->assertContains('Alice', $mentions);
        $this->assertContains('Bob', $mentions);
    }

    #[Test]
    public function it_parses_bracket_mention_with_title(): void
    {
        $mentions = $this->service->parseMentions('Hey @[John Smith (Developer)] check this');
        $this->assertSame(['John Smith'], $mentions);
    }

    #[Test]
    public function it_parses_bracket_mention_without_title(): void
    {
        $mentions = $this->service->parseMentions('Ask @[Jane Doe] about this');
        $this->assertSame(['Jane Doe'], $mentions);
    }

    #[Test]
    public function it_parses_multiple_bracket_mentions_and_deduplicates(): void
    {
        $mentions = $this->service->parseMentions(
            '@[Alice Jones (PM)] and @[Bob Lee (Dev)] — also loop in @[Alice Jones (PM)]'
        );
        $this->assertContains('Alice Jones', $mentions);
        $this->assertContains('Bob Lee', $mentions);
    }

    #[Test]
    public function it_parses_bare_mentions(): void
    {
        $mentions = $this->service->parseMentions('Hey @john check this');
        $this->assertContains('john', $mentions);
    }

    #[Test]
    public function it_ignores_empty_brackets(): void
    {
        $mentions = $this->service->parseMentions('@[] should not match');
        $this->assertSame([], $mentions);
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
