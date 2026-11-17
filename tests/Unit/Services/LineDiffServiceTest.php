<?php

namespace Tests\Unit\Services;

use App\Services\LineDiffService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class LineDiffServiceTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_marks_added_and_removed_lines(): void
    {
        $diff = (new LineDiffService)->diff(['a', 'b', 'c'], ['a', 'x', 'c']);

        $this->assertSame([
            ['text' => 'a', 'changed' => false],
            ['text' => 'b', 'changed' => true],
            ['text' => 'c', 'changed' => false],
        ], $diff['from']);
        $this->assertSame([
            ['text' => 'a', 'changed' => false],
            ['text' => 'x', 'changed' => true],
            ['text' => 'c', 'changed' => false],
        ], $diff['to']);
    }

    #[Test]
    public function it_marks_everything_unchanged_for_identical_input(): void
    {
        $diff = (new LineDiffService)->diff(['a', 'b'], ['a', 'b']);

        $this->assertTrue(collect($diff['from'])->every(fn ($line) => $line['changed'] === false));
        $this->assertTrue(collect($diff['to'])->every(fn ($line) => $line['changed'] === false));
    }

    #[Test]
    public function it_handles_empty_sides(): void
    {
        $diff = (new LineDiffService)->diff([], ['a']);

        $this->assertSame([], $diff['from']);
        $this->assertSame([['text' => 'a', 'changed' => true]], $diff['to']);
    }
}
