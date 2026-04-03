<?php

namespace Tests\Unit\Services;

use App\Models\KbArticle;
use App\Models\KbArticlePermission;
use App\Models\User;
use App\Services\KbSearchService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class KbSearchServiceTest extends TestCase
{
    use SeedsDatabase;

    private KbSearchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new KbSearchService;
    }

    #[Test]
    public function unauthenticated_users_see_only_public_verified_articles(): void
    {
        KbArticle::factory()->verified()->public()->create(['title' => 'Public Guide']);
        KbArticle::factory()->verified()->internal()->create(['title' => 'Internal Guide']);
        KbArticle::factory()->public()->create(['title' => 'Draft Public']); // draft

        $results = $this->service->search('', null);

        $this->assertCount(1, $results);
        $this->assertSame('Public Guide', $results->first()->title);
    }

    #[Test]
    public function authenticated_user_sees_internal_and_own_drafts(): void
    {
        $user = User::factory()->create();
        KbArticle::factory()->verified()->internal()->create(['title' => 'Internal']);
        KbArticle::factory()->create(['title' => 'My Draft', 'user_id' => $user->id, 'owner_id' => $user->id]);
        KbArticle::factory()->create(['title' => 'Other Draft']); // someone else's draft

        $results = $this->service->search('', $user);

        $titles = $results->pluck('title')->all();
        $this->assertContains('Internal', $titles);
        $this->assertContains('My Draft', $titles);
        $this->assertNotContains('Other Draft', $titles);
    }

    #[Test]
    public function restricted_articles_visible_only_to_permitted_users(): void
    {
        $owner = User::factory()->create();
        $permitted = User::factory()->create();
        $stranger = User::factory()->create();
        $article = KbArticle::factory()->verified()->restricted()->create(['owner_id' => $owner->id]);

        KbArticlePermission::create(['article_id' => $article->id, 'user_id' => $permitted->id]);

        $this->assertCount(1, $this->service->search('', $owner));
        $this->assertCount(1, $this->service->search('', $permitted));
        $this->assertCount(0, $this->service->search('', $stranger));
    }

    #[Test]
    public function it_filters_by_keyword(): void
    {
        KbArticle::factory()->verified()->public()->create(['title' => 'Laravel Authentication']);
        KbArticle::factory()->verified()->public()->create(['title' => 'React Components']);

        $results = $this->service->search('Laravel', null);

        $this->assertCount(1, $results);
        $this->assertSame('Laravel Authentication', $results->first()->title);
    }
}
