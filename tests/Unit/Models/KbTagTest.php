<?php

namespace Tests\Unit\Models;

use App\Models\KbTag;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class KbTagTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function creating_with_only_a_name_derives_the_slug(): void
    {
        $tag = KbTag::create(['name' => 'PHP Framework']);

        $this->assertSame('php-framework', $tag->slug);
    }

    #[Test]
    public function colliding_names_rejected_by_the_backstop(): void
    {
        KbTag::factory()->create(['name' => 'php', 'slug' => 'php']);

        try {
            KbTag::create(['name' => 'PHP!']);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('name', $exception->errors());
        }
    }

    #[Test]
    public function punctuation_only_names_rejected_by_the_backstop(): void
    {
        try {
            KbTag::create(['name' => '!!!']);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('name', $exception->errors());
        }
    }

    #[Test]
    public function concurrent_slug_collision_returns_a_validation_error(): void
    {
        KbTag::creating(function (): void {
            static $fired = false;

            if ($fired) {
                return;
            }

            $fired = true;

            DB::table('kb_tags')->insert([
                'name' => 'php',
                'slug' => 'php',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->expectException(ValidationException::class);

        KbTag::create(['name' => 'php']);
    }
}
