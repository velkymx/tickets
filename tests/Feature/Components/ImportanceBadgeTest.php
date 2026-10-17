<?php

namespace Tests\Feature\Components;

use App\Models\Importance;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class ImportanceBadgeTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function component_renders_icon_for_importance(): void
    {
        $importance = Importance::factory()->create([
            'name' => 'Critical',
            'icon' => 'fa-solid fa-fire',
            'class' => 'danger',
        ]);

        $html = Blade::render('<x-importance-badge :importance="$importance" />', [
            'importance' => $importance,
        ]);

        $this->assertStringContainsString($importance->icon, $html);
        $this->assertStringContainsString('text-'.$importance->class, $html);
    }

    #[Test]
    public function component_renders_fallback_without_importance(): void
    {
        $html = Blade::render('<x-importance-badge :importance="$importance" />', [
            'importance' => null,
        ]);

        $this->assertStringContainsString('Priority: none', $html);
    }

    #[Test]
    public function ticket_show_renders_badge_markup_for_importance(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $response = $this->actingAs($user)->get(route('tickets.show', $ticket));

        $response->assertStatus(200);
        $response->assertSee('title="Priority: '.$ticket->importance->name.'"', false);
    }

    #[Test]
    public function home_renders_ticket_with_missing_importance(): void
    {
        $user = User::factory()->create();
        $open = Status::factory()->create(['name' => 'Open Badge']);
        $ticket = Ticket::factory()->create([
            'user_id2' => $user->id,
            'status_id' => $open->id,
        ]);

        DB::table('importances')->where('id', $ticket->importance_id)->delete();

        $response = $this->actingAs($user)->get('/home');

        $response->assertStatus(200);
        $response->assertSee('Priority: none', false);
    }
}
