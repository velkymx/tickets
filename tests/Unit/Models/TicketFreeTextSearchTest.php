<?php

namespace Tests\Unit\Models;

use App\Models\Ticket;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class TicketFreeTextSearchTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function free_text_matches_subject_substring(): void
    {
        Ticket::factory()->create(['subject' => 'Login crash on checkout']);
        Ticket::factory()->create(['subject' => 'Unrelated billing page']);

        $found = Ticket::filter(['q' => 'login crash'])->pluck('subject');

        $this->assertContains('Login crash on checkout', $found);
        $this->assertNotContains('Unrelated billing page', $found);
    }

    #[Test]
    public function free_text_combines_with_structured_filters(): void
    {
        $hit = Ticket::factory()->create(['subject' => 'Urgent login fix', 'importance_id' => 5]);
        Ticket::factory()->create(['subject' => 'Urgent login fix', 'importance_id' => 1]);

        $found = Ticket::filter(['importance_id' => 5, 'q' => 'login'])->pluck('id');

        $this->assertContains($hit->id, $found);
        $this->assertCount(1, $found);
    }

    #[Test]
    public function percent_is_treated_as_literal_text(): void
    {
        Ticket::factory()->create(['subject' => '100% coverage']);
        Ticket::factory()->create(['subject' => '100X coverage']);

        $found = Ticket::filter(['q' => '100%'])->pluck('subject');

        $this->assertContains('100% coverage', $found);
        $this->assertNotContains('100X coverage', $found);
    }

    #[Test]
    public function underscore_is_treated_as_literal_text(): void
    {
        Ticket::factory()->create(['subject' => 'user_login flow']);
        Ticket::factory()->create(['subject' => 'userXlogin flow']);

        $found = Ticket::filter(['q' => 'user_login'])->pluck('subject');

        $this->assertContains('user_login flow', $found);
        $this->assertNotContains('userXlogin flow', $found);
    }

    #[Test]
    public function escape_character_is_treated_as_literal_text(): void
    {
        Ticket::factory()->create(['subject' => 'bang!here deploy']);
        Ticket::factory()->create(['subject' => 'bangXhere deploy']);

        $found = Ticket::filter(['q' => 'bang!here'])->pluck('subject');

        $this->assertContains('bang!here deploy', $found);
        $this->assertNotContains('bangXhere deploy', $found);
    }
}
