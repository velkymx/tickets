<?php

namespace App\Services;

class LineDiffService
{
    public function diff(array $from, array $to): array
    {
        $from = array_values($from);
        $to = array_values($to);
        $n = count($from);
        $m = count($to);

        $lengths = [];
        for ($i = $n - 1; $i >= 0; $i--) {
            $lengths[$i] = [];
            for ($j = $m - 1; $j >= 0; $j--) {
                if ($from[$i] === $to[$j]) {
                    $lengths[$i][$j] = ($lengths[$i + 1][$j + 1] ?? 0) + 1;
                } else {
                    $lengths[$i][$j] = max($lengths[$i + 1][$j] ?? 0, $lengths[$i][$j + 1] ?? 0);
                }
            }
        }

        $fromLines = [];
        $toLines = [];
        $i = 0;
        $j = 0;
        while ($i < $n && $j < $m) {
            if ($from[$i] === $to[$j]) {
                $fromLines[] = ['text' => $from[$i], 'changed' => false];
                $toLines[] = ['text' => $to[$j], 'changed' => false];
                $i++;
                $j++;
            } elseif (($lengths[$i + 1][$j] ?? 0) >= ($lengths[$i][$j + 1] ?? 0)) {
                $fromLines[] = ['text' => $from[$i], 'changed' => true];
                $i++;
            } else {
                $toLines[] = ['text' => $to[$j], 'changed' => true];
                $j++;
            }
        }
        while ($i < $n) {
            $fromLines[] = ['text' => $from[$i++], 'changed' => true];
        }
        while ($j < $m) {
            $toLines[] = ['text' => $to[$j++], 'changed' => true];
        }

        return ['from' => $fromLines, 'to' => $toLines];
    }
}
