<?php

namespace Tests\Unit\Models;

use App\Models\KbCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class KbCategoryTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function creating_with_only_a_name_derives_the_slug(): void
    {
        $category = KbCategory::create(['name' => 'Billing Docs']);

        $this->assertSame('billing-docs', $category->slug);
    }

    #[Test]
    public function colliding_names_rejected_by_the_backstop(): void
    {
        KbCategory::factory()->create(['name' => 'Billing', 'slug' => 'billing']);

        try {
            KbCategory::create(['name' => 'Billing!']);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('name', $exception->errors());
        }
    }

    #[Test]
    public function punctuation_only_names_rejected_by_the_backstop(): void
    {
        try {
            KbCategory::create(['name' => '!!!']);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('name', $exception->errors());
        }
    }

    #[Test]
    public function concurrent_slug_collision_returns_a_validation_error(): void
    {
        KbCategory::creating(function (): void {
            static $fired = false;

            if ($fired) {
                return;
            }

            $fired = true;

            DB::table('kb_categories')->insert([
                'name' => 'Billing',
                'slug' => 'billing',
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->expectException(ValidationException::class);

        KbCategory::create(['name' => 'Billing']);
    }
}
