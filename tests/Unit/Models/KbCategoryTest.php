<?php

namespace Tests\Unit\Models;

use App\Models\KbArticle;
use App\Models\KbCategory;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class KbCategoryTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_has_the_expected_fillable_fields(): void
    {
        $category = new KbCategory;
        $this->assertSame(['name', 'slug', 'description', 'sort_order'], $category->getFillable());
    }

    #[Test]
    public function it_has_many_articles(): void
    {
        $category = KbCategory::factory()->create();
        $article = KbArticle::factory()->create(['category_id' => $category->id]);

        $this->assertTrue($category->articles->contains($article));
    }
}
