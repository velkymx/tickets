<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\TicketsServer;
use App\Mcp\Tools\SearchTicketsTool;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class SearchTicketsToolTest extends TestCase
{
    use SeedsDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    #[Test]
    public function search_tool_rejects_unauthenticated_calls(): void
    {
        $response = TicketsServer::tool(SearchTicketsTool::class, ['query' => 'login']);

        $response->assertHasErrors(['Unauthenticated']);
    }

    #[Test]
    public function search_tool_finds_tickets_by_free_text(): void
    {
        $user = User::factory()->create();
        $hit = Ticket::factory()->create(['user_id2' => $user->id, 'subject' => 'Login crash on checkout']);
        Ticket::factory()->create(['subject' => 'Unrelated billing page']);

        $response = TicketsServer::actingAs($user)->tool(SearchTicketsTool::class, ['query' => 'login crash']);

        $response->assertOk();
        $response->assertSee($hit->subject);
        $response->assertDontSee('Unrelated billing page');
    }

    #[Test]
    public function search_tool_supports_filter_tokens(): void
    {
        $user = User::factory()->create();
        $mine = Ticket::factory()->create(['user_id2' => $user->id, 'subject' => 'My urgent fix']);
        Ticket::factory()->create(['subject' => 'Someone else urgent fix']);

        $response = TicketsServer::actingAs($user)->tool(SearchTicketsTool::class, ['query' => 'assignee:me urgent']);

        $response->assertOk();
        $response->assertSee($mine->subject);
        $response->assertDontSee('Someone else urgent fix');
    }

    #[Test]
    public function search_tool_paginates_results(): void
    {
        $user = User::factory()->create();
        Ticket::factory()->count(5)->create(['user_id2' => $user->id, 'subject' => 'Searchable pagination case']);

        $response = TicketsServer::actingAs($user)->tool(SearchTicketsTool::class, [
            'query' => 'pagination',
            'per_page' => 2,
        ]);

        $response->assertOk();
        $response->assertSee('total');
    }

    #[Test]
    public function search_tool_hides_unrelated_tickets(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Ticket::factory()->create(['user_id' => $other->id, 'user_id2' => $other->id, 'subject' => 'Foreign salary review']);

        $response = TicketsServer::actingAs($user)->tool(SearchTicketsTool::class, ['query' => 'salary']);

        $response->assertOk();
        $response->assertDontSee('Foreign salary review');
    }

    #[Test]
    public function search_tool_defaults_to_active_statuses(): void
    {
        $user = User::factory()->create();
        $closedId = Status::where('name', 'completed')->value('id') ?? Status::closedStatusIds()[0];
        Ticket::factory()->create(['user_id2' => $user->id, 'status_id' => $closedId, 'subject' => 'Done pagination audit']);

        $hidden = TicketsServer::actingAs($user)->tool(SearchTicketsTool::class, ['query' => 'audit']);
        $hidden->assertOk();
        $hidden->assertDontSee('Done pagination audit');

        $shown = TicketsServer::actingAs($user)->tool(SearchTicketsTool::class, ['query' => 'status:closed audit']);
        $shown->assertOk();
        $shown->assertSee('Done pagination audit');
    }
}
