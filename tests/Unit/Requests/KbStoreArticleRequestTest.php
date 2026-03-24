<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\Kb\StoreArticleRequest;
use App\Models\KbCategory;
use App\Models\KbTag;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class KbStoreArticleRequestTest extends TestCase
{
    use SeedsDatabase;

    private function rules(): array
    {
        return (new StoreArticleRequest)->rules();
    }

    #[Test]
    public function it_requires_title_body_category_visibility_commit_message_and_tags(): void
    {
        $validator = Validator::make([], $this->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
        $this->assertArrayHasKey('body_markdown', $validator->errors()->toArray());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('visibility', $validator->errors()->toArray());
        $this->assertArrayHasKey('commit_message', $validator->errors()->toArray());
        $this->assertArrayHasKey('tags', $validator->errors()->toArray());
    }

    #[Test]
    public function it_passes_with_valid_data(): void
    {
        $category = KbCategory::factory()->create();
        $tag = KbTag::factory()->create();

        $validator = Validator::make([
            'title' => 'My Article',
            'body_markdown' => 'Some content here',
            'category_id' => $category->id,
            'visibility' => 'internal',
            'commit_message' => 'Initial creation',
            'tags' => [$tag->id],
        ], $this->rules());

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_rejects_invalid_visibility(): void
    {
        $validator = Validator::make(['visibility' => 'secret'], $this->rules());

        $this->assertArrayHasKey('visibility', $validator->errors()->toArray());
    }
}
