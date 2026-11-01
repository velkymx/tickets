<?php

namespace Tests\Feature\Palette;

use App\Models\Ticket;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class PaletteSearchTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function search_returns_matching_tickets(): void
    {
        $user = User::factory()->create();
        $hit = Ticket::factory()->create(['subject' => 'Login crash on checkout']);
        Ticket::factory()->create(['subject' => 'Unrelated billing page']);

        $response = $this->actingAs($user)->getJson('/palette/search?q=login');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $hit->id, 'subject' => 'Login crash on checkout']);
        $response->assertJsonMissing(['subject' => 'Unrelated billing page']);
    }

    #[Test]
    public function short_queries_return_empty(): void
    {
        $user = User::factory()->create();
        Ticket::factory()->create(['subject' => 'Login crash']);

        $response = $this->actingAs($user)->getJson('/palette/search?q=l');

        $response->assertOk()->assertExactJson([]);
    }

    #[Test]
    public function overlong_queries_are_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/palette/search?q='.str_repeat('a', 501));

        $response->assertStatus(422);
    }
}
