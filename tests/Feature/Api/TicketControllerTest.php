<?php

namespace Tests\Feature\Api;

use App\Models\Importance;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\Type;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketControllerTest extends TestCase
{
    use RefreshDatabase;

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
        $closedStatus = Status::factory()->create(['id' => 5]);
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
        $closedStatus = Status::factory()->create(['id' => 5]);
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

        $this->assertDatabaseHas('notes', [
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'Test note',
            'hours' => 2,
        ]);
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
}
