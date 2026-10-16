<?php

namespace Tests\Feature\Tickets;

use App\Models\Ticket;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class ComposerMentionsTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function show_page_exposes_parseable_composer_data(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $html = $this->actingAs($user)->get(route('tickets.show', $ticket))->getContent();

        $this->assertStringContainsString('markdown-composer', $html);

        preg_match("/data-users='([^']*)'/", $html, $matches);
        $this->assertNotEmpty($matches, 'data-users attribute missing or empty');
        $decoded = json_decode(html_entity_decode($matches[1]), true);
        $this->assertIsArray($decoded, 'data-users value is not parseable JSON');

        preg_match("/data-commands='([^']*)'/", $html, $cmdMatches);
        $this->assertNotEmpty($cmdMatches, 'data-commands attribute missing or empty');
        $this->assertIsArray(json_decode(html_entity_decode($cmdMatches[1]), true));
    }
}
