<?php

namespace Tests\Unit\Models;

use App\Models\KbArticle;
use App\Models\KbArticleAttachment;
use App\Models\KbArticlePermission;
use App\Models\KbArticleVersion;
use App\Models\KbCategory;
use App\Models\KbTag;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class KbArticleTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_has_the_expected_fillable_fields(): void
    {
        $article = new KbArticle;
        $this->assertSame([
            'title', 'slug', 'body_markdown', 'body_html',
            'category_id', 'user_id', 'owner_id',
            'status', 'visibility', 'reviewed_at', 'published_at',
        ], $article->getFillable());
    }

    #[Test]
    public function it_belongs_to_a_category_creator_and_owner(): void
    {
        $user = User::factory()->create();
        $category = KbCategory::factory()->create();
        $article = KbArticle::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'owner_id' => $user->id,
        ]);

        $this->assertTrue($article->category->is($category));
        $this->assertTrue($article->creator->is($user));
        $this->assertTrue($article->owner->is($user));
    }

    #[Test]
    public function it_has_many_tags_via_pivot(): void
    {
        $article = KbArticle::factory()->create();
        $tag = KbTag::factory()->create();
        $article->tags()->attach($tag);

        $this->assertTrue($article->fresh()->tags->contains($tag));
    }

    #[Test]
    public function it_has_many_versions(): void
    {
        $article = KbArticle::factory()->create();
        $version = KbArticleVersion::create([
            'article_id' => $article->id,
            'user_id' => $article->user_id,
            'title' => $article->title,
            'body_markdown' => $article->body_markdown,
            'body_html' => $article->body_html,
            'commit_message' => 'Initial version',
            'version_number' => 1,
        ]);

        $this->assertTrue($article->versions->contains($version));
    }

    #[Test]
    public function it_has_many_permissions(): void
    {
        $article = KbArticle::factory()->create();
        $user = User::factory()->create();
        $permission = KbArticlePermission::create([
            'article_id' => $article->id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue($article->permissions->contains($permission));
    }

    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $article = KbArticle::factory()->create();
        $article->delete();

        $this->assertSoftDeleted($article);
        $this->assertNotNull(KbArticle::withTrashed()->find($article->id));
    }

    #[Test]
    public function it_casts_dates_correctly(): void
    {
        $article = KbArticle::factory()->verified()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $article->reviewed_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $article->published_at);
    }
}
