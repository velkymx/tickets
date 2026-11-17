<?php

namespace App\Services;

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\MemoryEfficientLongestCommonSubsequenceCalculator;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

class LineDiffService
{
    private const MAX_LINES = 5000;

    public function diff(array $from, array $to): array
    {
        $from = array_values($from);
        $to = array_values($to);

        if (count($from) + count($to) > self::MAX_LINES) {
            return $this->boundedDiff($from, $to);
        }

        $differ = new Differ(new UnifiedDiffOutputBuilder(''));
        $changes = $differ->diffToArray(
            $from,
            $to,
            new MemoryEfficientLongestCommonSubsequenceCalculator
        );

        $fromLines = [];
        $toLines = [];

        foreach ($changes as [$line, $status]) {
            $text = rtrim($line, "\n");

            if ($status === Differ::OLD) {
                $fromLines[] = ['text' => $text, 'changed' => false];
                $toLines[] = ['text' => $text, 'changed' => false];
            } elseif ($status === Differ::REMOVED) {
                $fromLines[] = ['text' => $text, 'changed' => true];
            } elseif ($status === Differ::ADDED) {
                $toLines[] = ['text' => $text, 'changed' => true];
            }
        }

        return ['from' => $fromLines, 'to' => $toLines];
    }

    private function boundedDiff(array $from, array $to): array
    {
        $max = min(count($from), count($to));
        $prefix = 0;

        while ($prefix < $max && $from[$prefix] === $to[$prefix]) {
            $prefix++;
        }

        $suffix = 0;

        while (
            $suffix < $max - $prefix
            && $from[count($from) - 1 - $suffix] === $to[count($to) - 1 - $suffix]
        ) {
            $suffix++;
        }

        return [
            'from' => $this->markLines($from, $prefix, $suffix),
            'to' => $this->markLines($to, $prefix, $suffix),
        ];
    }

    private function markLines(array $lines, int $prefix, int $suffix): array
    {
        $changedUntil = count($lines) - $suffix;
        $marked = [];

        foreach ($lines as $index => $line) {
            $marked[] = [
                'text' => $line,
                'changed' => $index >= $prefix && $index < $changedUntil,
            ];
        }

        return $marked;
    }
}
