<?php

namespace Tests\Feature\Api;

use App\Models\Importance;
use App\Models\Mention;
use App\Models\Milestone;
use App\Models\Note;
use App\Models\NoteAttachment;
use App\Models\NoteReaction;
use App\Models\Project;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\Type;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class TicketControllerTest extends TestCase
{
    use SeedsDatabase;

    protected string $token;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $plainToken = 'test-api-token-that-is-long-enough-for-sha256';
        $this->token = $plainToken;

        $this->user = User::factory()->create([
            'api_token' => hash('sha256', $plainToken),
        ]);
    }

    protected function apiHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ];
    }

    #[Test]
    public function health_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/health', $this->apiHeaders());

        $response->assertStatus(200)
            ->assertJson(['status' => 'ok']);
    }

    #[Test]
    public function lookups_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/lookups');

        $response->assertStatus(401)
            ->assertJson(['message' => 'Unauthorized']);
    }

    #[Test]
    public function lookups_returns_all_reference_data(): void
    {
        Status::factory()->create(['name' => 'Open']);
        Type::factory()->create(['name' => 'Bug']);
        Importance::factory()->create(['name' => 'High']);
        Project::factory()->create(['name' => 'Project 1', 'active' => 1]);
        Milestone::factory()->create(['name' => 'Sprint 1']);

        $response = $this->getJson('/api/v1/lookups', $this->apiHeaders());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'statuses',
                    'types',
                    'importance',
                    'projects',
                    'milestones',
                ],
            ]);
    }

    #[Test]
    public function index_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/tickets');

        $response->assertStatus(401);
    }

    #[Test]
    public function index_returns_tickets_for_authenticated_user(): void
    {
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);

        $response = $this->getJson('/api/v1/tickets', $this->apiHeaders());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'subject',
                        'estimate',
                        'status',
                        'importance',
                        'due_at',
                        'closed_at',
                        'created_at',
                        'link',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    #[Test]
    public function index_excludes_other_users_tickets(): void
    {
        $otherUser = User::factory()->create();
        Ticket::factory()->create(['user_id2' => $otherUser->id]);
        Ticket::factory()->create(['user_id2' => $this->user->id]);

        $response = $this->getJson('/api/v1/tickets', $this->apiHeaders());

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
    }

    #[Test]
    public function index_filters_by_status(): void
    {
        $openStatus = Status::factory()->create(['name' => 'Open']);
        $closedStatus = Status::factory()->closed()->create();
        Ticket::factory()->create(['user_id2' => $this->user->id, 'status_id' => $openStatus->id]);
        Ticket::factory()->create(['user_id2' => $this->user->id, 'status_id' => $closedStatus->id]);

        $response = $this->getJson('/api/v1/tickets?status='.$openStatus->id, $this->apiHeaders());

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
    }

    #[Test]
    public function index_filters_unassigned_tickets(): void
    {
        Ticket::factory()->create(['user_id2' => 0]);
        Ticket::factory()->create(['user_id2' => $this->user->id]);

        $response = $this->getJson('/api/v1/tickets?unassigned=true', $this->apiHeaders());

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
    }

    #[Test]
    public function index_respects_per_page_parameter(): void
    {
        Ticket::factory()->count(25)->create(['user_id2' => $this->user->id]);

        $response = $this->getJson('/api/v1/tickets?per_page=10', $this->apiHeaders());

        $response->assertStatus(200);
        $this->assertEquals(10, $response->json('meta.per_page'));
    }

    #[Test]
    public function store_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/tickets', [
            'subject' => 'New Ticket',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function store_validates_required_subject(): void
    {
        $response = $this->postJson('/api/v1/tickets', [], $this->apiHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subject']);
    }

    #[Test]
    public function store_validates_foreign_key_ids(): void
    {
        $response = $this->postJson('/api/v1/tickets', [
            'subject' => 'Test',
            'type_id' => 99999,
            'importance_id' => 99999,
            'project_id' => 99999,
            'milestone_id' => 99999,
        ], $this->apiHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type_id', 'importance_id', 'project_id', 'milestone_id']);
    }

    #[Test]
    public function store_creates_ticket(): void
    {
        $response = $this->postJson('/api/v1/tickets', [
            'subject' => 'New Ticket',
        ], $this->apiHeaders());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'ticket' => [
                    'id',
                    'subject',
                    'status',
                    'link',
                ],
            ]);

        $this->assertDatabaseHas('tickets', [
            'subject' => 'New Ticket',
            'user_id' => $this->user->id,
            'user_id2' => $this->user->id,
        ]);
    }

    #[Test]
    public function store_accepts_optional_fields(): void
    {
        $project = Project::factory()->create();
        $milestone = Milestone::factory()->create();
        $type = Type::factory()->create();
        $importance = Importance::factory()->create();
        $status = Status::factory()->create();

        $response = $this->postJson('/api/v1/tickets', [
            'subject' => 'Detailed Ticket',
            'description' => 'This is a test ticket',
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'type_id' => $type->id,
            'importance_id' => $importance->id,
            'estimate' => 5,
            'storypoints' => 3,
            'due_at' => '2024-12-31',
        ], $this->apiHeaders());

        $response->assertStatus(201);

        $ticket = Ticket::first();
        $this->assertEquals('Detailed Ticket', $ticket->subject);
        $this->assertEquals('This is a test ticket', $ticket->description);
        $this->assertEquals($project->id, $ticket->project_id);
    }

    #[Test]
    public function show_requires_authentication(): void
    {
        $ticket = Ticket::factory()->create();

        $response = $this->getJson("/api/v1/tickets/{$ticket->id}");

        $response->assertStatus(401);
    }

    #[Test]
    public function show_returns_ticket_details(): void
    {
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);

        $response = $this->getJson("/api/v1/tickets/{$ticket->id}", $this->apiHeaders());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'subject',
                    'description',
                    'estimate',
                    'storypoints',
                    'status',
                    'type',
                    'importance',
                    'milestone',
                    'project',
                    'assignee',
                    'due_at',
                    'closed_at',
                    'created_at',
                    'notes',
                ],
            ]);
    }

    #[Test]
    public function show_excludes_hidden_notes(): void
    {
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);
        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'Visible note',
            'hide' => false,
        ]);
        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'Hidden note',
            'hide' => true,
        ]);

        $response = $this->getJson("/api/v1/tickets/{$ticket->id}", $this->apiHeaders());

        $response->assertStatus(200);
        $notes = $response->json('data.notes');
        $this->assertCount(1, $notes);
        $this->assertEquals('Visible note', $notes[0]['body']);
    }

    #[Test]
    public function show_returns_404_for_other_users_ticket(): void
    {
        $otherUser = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $otherUser->id]);

        $response = $this->getJson("/api/v1/tickets/{$ticket->id}", $this->apiHeaders());

        $response->assertStatus(404);
    }

    #[Test]
    public function note_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/tickets/1/note');

        $response->assertStatus(401);
    }

    #[Test]
    public function note_claims_ticket_when_requested(): void
    {
        // API note endpoint filters tickets by user_id2 = api_user,
        // so claim sets user_id2 to current user (redundant but idempotent)
        $ticket = Ticket::factory()->create([
            'user_id2' => $this->user->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->postJson("/api/v1/tickets/{$ticket->id}/note", [
            'claim' => true,
        ], $this->apiHeaders());

        $response->assertStatus(200);

        $ticket->refresh();
        $this->assertEquals($this->user->id, $ticket->user_id2);
    }

    #[Test]
    public function note_updates_status(): void
    {
        $openStatus = Status::factory()->create(['name' => 'Open']);
        $closedStatus = Status::factory()->closed()->create();
        $ticket = Ticket::factory()->create([
            'user_id2' => $this->user->id,
            'status_id' => $openStatus->id,
        ]);

        $response = $this->postJson("/api/v1/tickets/{$ticket->id}/note", [
            'status_id' => $closedStatus->id,
        ], $this->apiHeaders());

        $response->assertStatus(200);

        $ticket->refresh();
        $this->assertEquals($closedStatus->id, $ticket->status_id);
    }

    #[Test]
    public function note_creates_note_entry(): void
    {
        $ticket = Ticket::factory()->create([
            'user_id2' => $this->user->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->postJson("/api/v1/tickets/{$ticket->id}/note", [
            'body' => 'Test note',
            'hours' => 2,
        ], $this->apiHeaders());

        $response->assertStatus(200);

        $note = Note::where('ticket_id', $ticket->id)->where('user_id', $this->user->id)->latest('id')->first();
        $this->assertNotNull($note);
        $this->assertStringContainsString('<p>Test note</p>', $note->body);
        $this->assertEquals('Test note', $note->body_markdown);
        $this->assertEquals(2, (int) $note->hours);
    }

    #[Test]
    public function note_stores_body_as_html_and_body_markdown_as_raw(): void
    {
        $ticket = Ticket::factory()->create([
            'user_id2' => $this->user->id,
            'user_id' => $this->user->id,
        ]);

        $this->postJson("/api/v1/tickets/{$ticket->id}/note", [
            'body' => '**bold** text',
        ], $this->apiHeaders());

        $note = Note::where('ticket_id', $ticket->id)->latest('id')->first();
        $this->assertStringContainsString('<strong>bold</strong>', $note->body);
        $this->assertEquals('**bold** text', $note->body_markdown);
    }

    #[Test]
    public function show_returns_enriched_note_shape(): void
    {
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);
        $otherUser = User::factory()->create(['name' => 'Sarah']);

        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $otherUser->id,
            'body' => 'We decided to use Redis',
            'body_markdown' => '<p>We decided to use Redis</p>',
            'notetype' => 'decision',
            'pinned' => true,
            'hours' => 2.5,
        ]);

        // Add a reaction
        NoteReaction::create(['note_id' => $note->id, 'user_id' => $this->user->id, 'emoji' => 'thumbsup']);

        // Add a reply
        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'parent_id' => $note->id,
            'body' => 'Agreed',
        ]);

        // Add an attachment
        NoteAttachment::create([
            'note_id' => $note->id,
            'user_id' => $otherUser->id,
            'ticket_id' => $ticket->id,
            'filename' => 'screenshot.png',
            'path' => 'attachments/1/screenshot.png',
            'mime_type' => 'image/png',
            'size' => 1024,
        ]);

        // Add a mention
        Mention::create(['note_id' => $note->id, 'user_id' => $this->user->id]);

        $response = $this->getJson("/api/v1/tickets/{$ticket->id}", $this->apiHeaders());

        $response->assertStatus(200);

        $notes = $response->json('data.notes');
        $this->assertCount(1, $notes, 'Should only return top-level notes');

        $firstNote = $notes[0];
        $this->assertEquals($note->id, $firstNote['id']);
        $this->assertEquals('Sarah', $firstNote['user']['name']);
        $this->assertEquals($otherUser->id, $firstNote['user']['id']);
        $this->assertEquals('We decided to use Redis', $firstNote['body']);
        $this->assertEquals('<p>We decided to use Redis</p>', $firstNote['body_markdown']);
        $this->assertEquals('decision', $firstNote['notetype']);
        $this->assertTrue($firstNote['pinned']);
        $this->assertEquals(2.5, $firstNote['hours']);
        $this->assertNull($firstNote['parent_id']);
        $this->assertFalse($firstNote['resolved']);

        // Reactions grouped
        $this->assertArrayHasKey('thumbsup', $firstNote['reactions']);
        $this->assertEquals(1, $firstNote['reactions']['thumbsup']['count']);
        $this->assertTrue($firstNote['reactions']['thumbsup']['reacted']);

        // Replies nested
        $this->assertCount(1, $firstNote['replies']);
        $this->assertEquals('Agreed', $firstNote['replies'][0]['body']);

        // Attachments
        $this->assertCount(1, $firstNote['attachments']);
        $this->assertEquals('screenshot.png', $firstNote['attachments'][0]['filename']);
        $this->assertTrue($firstNote['attachments'][0]['is_image']);

        // Mentions
        $this->assertCount(1, $firstNote['mentions']);
        $this->assertEquals($this->user->id, $firstNote['mentions'][0]['user']['id']);
    }

    #[Test]
    public function show_orders_notes_ascending_and_filters_top_level(): void
    {
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);

        $note1 = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'First',
            'created_at' => now()->subHour(),
        ]);

        $note2 = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'Second',
            'created_at' => now(),
        ]);

        // Reply should NOT appear as top-level
        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'Reply',
            'parent_id' => $note1->id,
        ]);

        $response = $this->getJson("/api/v1/tickets/{$ticket->id}", $this->apiHeaders());

        $response->assertStatus(200);
        $notes = $response->json('data.notes');
        $this->assertCount(2, $notes);
        $this->assertEquals('First', $notes[0]['body']);
        $this->assertEquals('Second', $notes[1]['body']);
    }

    #[Test]
    public function show_filters_notes_by_notetype(): void
    {
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);

        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'A regular message',
            'notetype' => 'message',
        ]);

        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'Use Redis for caching',
            'notetype' => 'decision',
        ]);

        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'Waiting on vendor',
            'notetype' => 'blocker',
        ]);

        // Filter to decisions only
        $response = $this->getJson("/api/v1/tickets/{$ticket->id}?notetype=decision", $this->apiHeaders());
        $response->assertStatus(200);
        $notes = $response->json('data.notes');
        $this->assertCount(1, $notes);
        $this->assertEquals('decision', $notes[0]['notetype']);

        // Filter to blockers only
        $response = $this->getJson("/api/v1/tickets/{$ticket->id}?notetype=blocker", $this->apiHeaders());
        $notes = $response->json('data.notes');
        $this->assertCount(1, $notes);
        $this->assertEquals('blocker', $notes[0]['notetype']);

        // No filter returns all
        $response = $this->getJson("/api/v1/tickets/{$ticket->id}", $this->apiHeaders());
        $notes = $response->json('data.notes');
        $this->assertCount(3, $notes);
    }

    #[Test]
    public function show_returns_pulse_object(): void
    {
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);

        // Create a blocker note so pulse has interesting data
        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'Waiting on API key from vendor',
            'notetype' => 'blocker',
            'resolved' => false,
        ]);

        $response = $this->getJson("/api/v1/tickets/{$ticket->id}", $this->apiHeaders());

        $response->assertStatus(200);

        $pulse = $response->json('data.pulse');
        $this->assertNotNull($pulse, 'Response should include pulse object');
        $this->assertArrayHasKey('execution_state', $pulse);
        $this->assertArrayHasKey('is_blocked', $pulse);
        $this->assertArrayHasKey('blocker_reason', $pulse);
        $this->assertArrayHasKey('latest_blocker', $pulse);
        $this->assertArrayHasKey('latest_decision', $pulse);
        $this->assertArrayHasKey('open_threads', $pulse);
        $this->assertArrayHasKey('last_activity_at', $pulse);

        $this->assertTrue($pulse['is_blocked']);
        $this->assertEquals('BLOCKED', $pulse['execution_state']);
        $this->assertStringContainsString('API key', $pulse['blocker_reason']);
    }

    #[Test]
    public function note_returns_updated_ticket_info(): void
    {
        $ticket = Ticket::factory()->create([
            'user_id2' => $this->user->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->postJson("/api/v1/tickets/{$ticket->id}/note", [
            'body' => 'Test note',
        ], $this->apiHeaders());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'ticket' => [
                    'id',
                    'status',
                    'assignee',
                ],
            ]);
    }

    #[Test]
    public function note_creates_decision_via_slash_command(): void
    {
        $ticket = Ticket::factory()->create([
            'user_id2' => $this->user->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->postJson("/api/v1/tickets/{$ticket->id}/note", [
            'body' => '/decision We will use Redis for caching',
        ], $this->apiHeaders());

        $response->assertStatus(200);
        $this->assertEquals('decision', $response->json('note.notetype'));
        $this->assertStringContainsString('Redis', $response->json('note.body'));
        $this->assertNotNull($response->json('note.body_markdown'));
        $this->assertNotNull($response->json('note.id'));

        $this->assertDatabaseHas('notes', [
            'ticket_id' => $ticket->id,
            'notetype' => 'decision',
        ]);
    }

    #[Test]
    public function note_creates_blocker_via_slash_command(): void
    {
        $ticket = Ticket::factory()->create([
            'user_id2' => $this->user->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->postJson("/api/v1/tickets/{$ticket->id}/note", [
            'body' => '/blocker Waiting on API key from vendor',
        ], $this->apiHeaders());

        $response->assertStatus(200);
        $this->assertEquals('blocker', $response->json('note.notetype'));

        $this->assertDatabaseHas('notes', [
            'ticket_id' => $ticket->id,
            'notetype' => 'blocker',
        ]);
    }

    #[Test]
    public function note_returns_warnings_for_unknown_commands(): void
    {
        $ticket = Ticket::factory()->create([
            'user_id2' => $this->user->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->postJson("/api/v1/tickets/{$ticket->id}/note", [
            'body' => '/statsu testing',
        ], $this->apiHeaders());

        $response->assertStatus(200);
        $warnings = $response->json('warnings');
        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString('statsu', $warnings[0]);
    }

    #[Test]
    public function note_returns_expanded_note_format(): void
    {
        $ticket = Ticket::factory()->create([
            'user_id2' => $this->user->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->postJson("/api/v1/tickets/{$ticket->id}/note", [
            'body' => 'A regular message',
        ], $this->apiHeaders());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'warnings',
                'ticket' => ['id', 'status', 'assignee'],
                'note' => [
                    'id', 'notetype', 'body', 'body_markdown',
                    'pinned', 'reactions', 'replies', 'attachments', 'mentions',
                ],
            ]);
    }

    #[Test]
    public function react_toggles_reaction_and_returns_grouped_counts(): void
    {
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
        ]);

        // Add reaction
        $response = $this->postJson("/api/v1/tickets/{$ticket->id}/notes/{$note->id}/react", [
            'emoji' => 'thumbsup',
        ], $this->apiHeaders());

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('reactions.thumbsup.count'));
        $this->assertTrue($response->json('reactions.thumbsup.reacted'));

        // Toggle off
        $response = $this->postJson("/api/v1/tickets/{$ticket->id}/notes/{$note->id}/react", [
            'emoji' => 'thumbsup',
        ], $this->apiHeaders());

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('thumbsup', $response->json('reactions'));
    }

    #[Test]
    public function reply_creates_threaded_reply(): void
    {
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->postJson("/api/v1/tickets/{$ticket->id}/notes/{$note->id}/reply", [
            'body' => 'Agreed, good call',
        ], $this->apiHeaders());

        $response->assertStatus(200);
        $this->assertEquals($note->id, $response->json('note.parent_id'));
        $this->assertStringContainsString('good call', $response->json('note.body'));
        $this->assertNotNull($response->json('note.body_markdown'));

        $this->assertDatabaseHas('notes', [
            'ticket_id' => $ticket->id,
            'parent_id' => $note->id,
            'user_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function reply_rejects_nested_reply(): void
    {
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);
        $parent = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
        ]);
        $reply = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'parent_id' => $parent->id,
        ]);

        $response = $this->postJson("/api/v1/tickets/{$ticket->id}/notes/{$reply->id}/reply", [
            'body' => 'Nested reply attempt',
        ], $this->apiHeaders());

        $response->assertStatus(422);
    }

    #[Test]
    public function edit_updates_note_and_sets_edited_at(): void
    {
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'Original text',
            'notetype' => 'message',
        ]);

        $response = $this->putJson("/api/v1/tickets/{$ticket->id}/notes/{$note->id}", [
            'body' => 'Updated text',
        ], $this->apiHeaders());

        $response->assertStatus(200);
        $this->assertNotNull($response->json('note.edited_at'));
        $this->assertNotNull($response->json('note.body_markdown'));

        $note->refresh();
        $this->assertStringContainsString('<p>Updated text</p>', $note->body);
        $this->assertEquals('Updated text', $note->body_markdown);
        $this->assertNotNull($note->edited_at);
    }

    #[Test]
    public function edit_blocks_decision_editing(): void
    {
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'We decided X',
            'notetype' => 'decision',
        ]);

        $response = $this->putJson("/api/v1/tickets/{$ticket->id}/notes/{$note->id}", [
            'body' => 'Changed decision',
        ], $this->apiHeaders());

        $response->assertStatus(422);
        $this->assertStringContainsString('decision', strtolower($response->json('message')));
    }

    #[Test]
    public function resolve_marks_note_resolved(): void
    {
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'notetype' => 'blocker',
            'resolved' => false,
        ]);

        $response = $this->postJson("/api/v1/tickets/{$ticket->id}/notes/{$note->id}/resolve", [
            'resolution_message' => 'Fixed in commit abc123',
        ], $this->apiHeaders());

        $response->assertStatus(200);
        $this->assertTrue($response->json('note.resolved'));

        $note->refresh();
        $this->assertTrue($note->resolved);
        $this->assertEquals($this->user->id, $note->resolved_by);
        $this->assertEquals('Fixed in commit abc123', $note->resolution_message);

        // Verify resolution reply stores body=HTML, body_markdown=raw
        $reply = Note::where('parent_id', $note->id)->first();
        $this->assertNotNull($reply);
        $this->assertStringContainsString('<p>Fixed in commit abc123</p>', $reply->body);
        $this->assertEquals('Fixed in commit abc123', $reply->body_markdown);
    }

    #[Test]
    public function resolve_requires_resolution_message(): void
    {
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->postJson("/api/v1/tickets/{$ticket->id}/notes/{$note->id}/resolve", [], $this->apiHeaders());

        $response->assertStatus(422);
    }

    #[Test]
    public function resolve_enforces_author_or_assignee(): void
    {
        $otherUser = User::factory()->create();
        // API user created the ticket but is not the assignee and not the note author
        $ticket = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'user_id2' => $otherUser->id,
        ]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->postJson("/api/v1/tickets/{$ticket->id}/notes/{$note->id}/resolve", [
            'resolution_message' => 'Trying to resolve',
        ], $this->apiHeaders());

        $response->assertStatus(403);
    }

    #[Test]
    public function all_api_routes_are_registered(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1/'))
            ->map(fn ($route) => $route->methods()[0].' '.$route->uri())
            ->sort()
            ->values()
            ->toArray();

        $this->assertContains('GET api/v1/tickets/{id}', $routes);
        $this->assertContains('GET api/v1/tickets/{id}/pulse', $routes);
        $this->assertContains('POST api/v1/tickets/{id}/note', $routes);
        $this->assertContains('POST api/v1/tickets/{id}/notes/{noteId}/react', $routes);
        $this->assertContains('POST api/v1/tickets/{id}/notes/{noteId}/reply', $routes);
        $this->assertContains('PUT api/v1/tickets/{id}/notes/{noteId}', $routes);
        $this->assertContains('POST api/v1/tickets/{id}/notes/{noteId}/resolve', $routes);
    }

    #[Test]
    public function index_includes_pulse_summary_when_requested(): void
    {
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);

        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'Blocked by vendor',
            'notetype' => 'blocker',
            'resolved' => false,
        ]);

        $response = $this->getJson('/api/v1/tickets?include=pulse', $this->apiHeaders());

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('pulse_summary', $data[0]);
        $this->assertArrayHasKey('execution_state', $data[0]['pulse_summary']);
        $this->assertArrayHasKey('is_blocked', $data[0]['pulse_summary']);
        $this->assertTrue($data[0]['pulse_summary']['is_blocked']);
    }

    #[Test]
    public function index_excludes_pulse_summary_by_default(): void
    {
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);

        $response = $this->getJson('/api/v1/tickets', $this->apiHeaders());

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertArrayNotHasKey('pulse_summary', $data[0]);
    }

    #[Test]
    public function pulse_endpoint_returns_cached_data(): void
    {
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);

        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'Use Redis',
            'notetype' => 'decision',
        ]);

        $response = $this->getJson("/api/v1/tickets/{$ticket->id}/pulse", $this->apiHeaders());

        $response->assertStatus(200);
        $this->assertArrayHasKey('execution_state', $response->json('data'));
        $this->assertArrayHasKey('is_blocked', $response->json('data'));
        $this->assertArrayHasKey('latest_decision', $response->json('data'));
        $this->assertArrayHasKey('last_activity_at', $response->json('data'));
    }

    #[Test]
    public function edit_enforces_author_only(): void
    {
        $otherUser = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $otherUser->id,
            'body' => 'Not my note',
        ]);

        $response = $this->putJson("/api/v1/tickets/{$ticket->id}/notes/{$note->id}", [
            'body' => 'Trying to edit',
        ], $this->apiHeaders());

        $response->assertStatus(403);
    }

    #[Test]
    public function react_validates_emoji(): void
    {
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->postJson("/api/v1/tickets/{$ticket->id}/notes/{$note->id}/react", [
            'emoji' => 'invalid_emoji',
        ], $this->apiHeaders());

        $response->assertStatus(422);
    }

    #[Test]
    public function note_with_action_without_mention_returns_422(): void
    {
        $ticket = Ticket::factory()->create([
            'user_id2' => $this->user->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->postJson("/api/v1/tickets/{$ticket->id}/note", [
            'body' => '/action Do this task',
        ], $this->apiHeaders());

        $response->assertStatus(422);
    }

    #[Test]
    public function note_with_action_and_mention_creates_action(): void
    {
        $assignee = User::factory()->create(['name' => 'Sarah']);
        $ticket = Ticket::factory()->create([
            'user_id2' => $this->user->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->postJson("/api/v1/tickets/{$ticket->id}/note", [
            'body' => '/action @Sarah verify fix on staging',
        ], $this->apiHeaders());

        $response->assertStatus(200);
        $this->assertEquals('action', $response->json('note.notetype'));

        $this->assertDatabaseHas('notes', [
            'ticket_id' => $ticket->id,
            'notetype' => 'action',
        ]);
    }

    #[Test]
    public function note_with_status_while_blocked_returns_422(): void
    {
        $status = Status::factory()->create(['name' => 'Testing']);
        $ticket = Ticket::factory()->create([
            'user_id2' => $this->user->id,
            'user_id' => $this->user->id,
        ]);

        // Create active blocker
        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'notetype' => 'blocker',
            'resolved' => false,
        ]);

        $response = $this->postJson("/api/v1/tickets/{$ticket->id}/note", [
            'body' => '/status testing',
        ], $this->apiHeaders());

        $response->assertStatus(422);
        $this->assertStringContainsString('blocker', strtolower($response->json('message')));
    }

    #[Test]
    public function backward_compatibility_existing_consumers_get_expected_fields(): void
    {
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);
        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'Test note',
        ]);

        $response = $this->getJson("/api/v1/tickets/{$ticket->id}", $this->apiHeaders());

        $response->assertStatus(200);
        $data = $response->json('data');

        // Existing fields still present
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('subject', $data);
        $this->assertArrayHasKey('description', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('importance', $data);
        $this->assertArrayHasKey('milestone', $data);
        $this->assertArrayHasKey('project', $data);
        $this->assertArrayHasKey('assignee', $data);
        $this->assertArrayHasKey('notes', $data);

        // New additive fields
        $this->assertArrayHasKey('pulse', $data);

        // Notes still have body and created_at
        $note = $data['notes'][0];
        $this->assertArrayHasKey('body', $note);
        $this->assertArrayHasKey('created_at', $note);
        $this->assertArrayHasKey('notetype', $note);
        $this->assertArrayHasKey('reactions', $note);
    }
}
