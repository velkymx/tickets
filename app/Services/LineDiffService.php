<?php

namespace App\Services;

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\MemoryEfficientLongestCommonSubsequenceCalculator;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

class LineDiffService
{
    public function diff(array $from, array $to): array
    {
        $differ = new Differ(new UnifiedDiffOutputBuilder(''));
        $changes = $differ->diffToArray(
            array_values($from),
            array_values($to),
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
}
