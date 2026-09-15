<?php

namespace Tests\Unit\Services;

use App\Models\Project;
use App\Models\User;
use App\Services\TicketQueryParser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class TicketQueryParserTest extends TestCase
{
    use SeedsDatabase;

    private function parser(): TicketQueryParser
    {
        return app(TicketQueryParser::class);
    }

    #[Test]
    public function it_parses_importance_by_name(): void
    {
        // DefaultsSeeder seeds blocker as importance id 5.
        $this->assertSame(['importance_id' => 5], $this->parser()->parse('importance:blocker'));
    }

    #[Test]
    public function it_parses_type_by_name(): void
    {
        // bug is type id 1.
        $this->assertSame(['type_id' => 1], $this->parser()->parse('type:bug'));
    }

    #[Test]
    public function status_open_maps_to_active_set(): void
    {
        $this->assertSame(['status_id' => 'none'], $this->parser()->parse('status:open'));
        $this->assertSame(['status_id' => 'none'], $this->parser()->parse('is:open'));
    }

    #[Test]
    public function status_active_resolves_the_active_status_by_name(): void
    {
        $active = \App\Models\Status::whereRaw('LOWER(name) = ?', ['active'])->firstOrFail();

        $this->assertSame(['status_id' => $active->id], $this->parser()->parse('status:active'));
    }

    #[Test]
    public function status_closed_maps_to_closed_set(): void
    {
        $this->assertSame(['status_id' => 'closed'], $this->parser()->parse('status:closed'));
        $this->assertSame(['status_id' => 'closed'], $this->parser()->parse('is:closed'));
    }

    #[Test]
    public function assignee_me_sets_the_assignee_flag(): void
    {
        $this->assertSame(['assignee' => 'me'], $this->parser()->parse('assignee:me'));
    }

    #[Test]
    public function assignee_by_name_resolves_to_user_id2(): void
    {
        $user = User::factory()->create(['name' => 'Alice']);
        $this->assertSame(['user_id2' => $user->id], $this->parser()->parse('assignee:alice'));
    }

    #[Test]
    public function project_accepts_id_or_quoted_name(): void
    {
        $project = Project::factory()->create(['name' => 'Big Thing']);
        $this->assertSame(['project_id' => $project->id], $this->parser()->parse('project:'.$project->id));
        $this->assertSame(['project_id' => $project->id], $this->parser()->parse('project:"Big Thing"'));
    }

    #[Test]
    public function bare_words_become_a_subject_search(): void
    {
        $this->assertSame(['q' => 'login crash'], $this->parser()->parse('login crash'));
    }

    #[Test]
    public function it_combines_tokens_and_free_text(): void
    {
        $this->assertSame(
            ['importance_id' => 5, 'q' => 'urgent fix'],
            $this->parser()->parse('importance:blocker urgent fix')
        );
    }

    #[Test]
    public function unknown_tokens_are_ignored(): void
    {
        $this->assertSame([], $this->parser()->parse('status:nope'));
        $this->assertSame([], $this->parser()->parse('project:"No Such Project"'));
        $this->assertSame([], $this->parser()->parse('bogus:whatever'));
    }

    #[Test]
    public function tokenize_returns_filter_tokens_for_chips(): void
    {
        $tokens = $this->parser()->tokenize('status:active importance:blocker hello');
        $this->assertSame(['status:active', 'importance:blocker'], array_column($tokens, 'raw'));
    }
}
