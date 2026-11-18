<?php

namespace Tests\Unit\Services;

use App\Services\LineDiffService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LineDiffServiceTest extends TestCase
{
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

    #[Test]
    public function it_bounds_large_diffs_and_preserves_common_prefix_and_suffix(): void
    {
        $from = array_merge(['alpha'], array_fill(0, 6000, 'old line'), ['omega']);
        $to = array_merge(['alpha'], array_fill(0, 6000, 'new line'), ['omega']);

        $diff = (new LineDiffService)->diff($from, $to);

        $this->assertCount(6002, $diff['from']);
        $this->assertCount(6002, $diff['to']);
        $this->assertSame('old line', $diff['from'][1]['text']);
        $this->assertSame('new line', $diff['to'][1]['text']);
        $this->assertFalse($diff['from'][0]['changed']);
        $this->assertTrue($diff['from'][1]['changed']);
        $this->assertFalse($diff['from'][array_key_last($diff['from'])]['changed']);
        $this->assertFalse($diff['to'][0]['changed']);
        $this->assertTrue($diff['to'][1]['changed']);
        $this->assertFalse($diff['to'][array_key_last($diff['to'])]['changed']);
        $this->assertSame(
            array_column($diff['from'], 'changed'),
            array_column($diff['to'], 'changed')
        );
    }
}
