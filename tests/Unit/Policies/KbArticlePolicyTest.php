<?php

namespace Tests\Unit\Policies;

use App\Models\KbArticle;
use App\Models\KbArticlePermission;
use App\Models\User;
use App\Policies\KbArticlePolicy;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class KbArticlePolicyTest extends TestCase
{
    use SeedsDatabase;

    private KbArticlePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new KbArticlePolicy;
    }

    #[Test]
    public function admin_can_do_everything(): void
    {
        $admin = User::factory()->create(['admin' => true]);

        $this->assertTrue($this->policy->before($admin, 'view'));
    }

    #[Test]
    public function kb_admin_can_do_everything(): void
    {
        $kbAdmin = User::factory()->create(['kb_role' => 'admin']);

        $this->assertTrue($this->policy->before($kbAdmin, 'view'));
    }

    #[Test]
    public function anyone_can_view_public_verified_articles(): void
    {
        $article = KbArticle::factory()->verified()->public()->create();

        $this->assertTrue($this->policy->view(null, $article));
    }

    #[Test]
    public function unauthenticated_cannot_view_internal_articles(): void
    {
        $article = KbArticle::factory()->verified()->internal()->create();

        $this->assertFalse($this->policy->view(null, $article));
    }

    #[Test]
    public function authenticated_user_can_view_internal_articles(): void
    {
        $user = User::factory()->create();
        $article = KbArticle::factory()->verified()->internal()->create();

        $this->assertTrue($this->policy->view($user, $article));
    }

    #[Test]
    public function only_permitted_users_can_view_restricted_articles(): void
    {
        $owner = User::factory()->create();
        $permitted = User::factory()->create();
        $stranger = User::factory()->create();
        $article = KbArticle::factory()->verified()->restricted()->create(['owner_id' => $owner->id]);

        KbArticlePermission::create(['article_id' => $article->id, 'user_id' => $permitted->id]);

        $this->assertTrue($this->policy->view($owner, $article));
        $this->assertTrue($this->policy->view($permitted, $article));
        $this->assertFalse($this->policy->view($stranger, $article));
    }

    #[Test]
    public function only_author_can_view_own_draft(): void
    {
        $author = User::factory()->create(['kb_role' => 'author']);
        $other = User::factory()->create(['kb_role' => 'author']);
        $article = KbArticle::factory()->create(['user_id' => $author->id, 'owner_id' => $author->id, 'status' => 'draft']);

        $this->assertTrue($this->policy->view($author, $article));
        $this->assertFalse($this->policy->view($other, $article));
    }

    #[Test]
    public function only_kb_authors_can_create(): void
    {
        $author = User::factory()->create(['kb_role' => 'author']);
        $reader = User::factory()->create(['kb_role' => null]);

        $this->assertTrue($this->policy->create($author));
        $this->assertFalse($this->policy->create($reader));
    }

    #[Test]
    public function owner_can_update_own_article(): void
    {
        $owner = User::factory()->create(['kb_role' => 'author']);
        $article = KbArticle::factory()->create(['owner_id' => $owner->id]);

        $this->assertTrue($this->policy->update($owner, $article));
    }

    #[Test]
    public function non_owner_author_cannot_update_unless_permitted(): void
    {
        $author = User::factory()->create(['kb_role' => 'author']);
        $article = KbArticle::factory()->create();

        $this->assertFalse($this->policy->update($author, $article));
    }

    #[Test]
    public function only_admins_can_delete(): void
    {
        $author = User::factory()->create(['kb_role' => 'author']);
        $article = KbArticle::factory()->create(['owner_id' => $author->id]);

        $this->assertFalse($this->policy->delete($author, $article));
    }

    #[Test]
    public function only_admins_can_restore(): void
    {
        $author = User::factory()->create(['kb_role' => 'author']);
        $article = KbArticle::factory()->create(['owner_id' => $author->id]);

        $this->assertFalse($this->policy->restore($author, $article));
    }
}
