<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class MarkdownService
{
    public function parse(?string $text): string
    {
        if (empty($text)) {
            return '';
        }

        $text = $this->wrapStackTrace($text);

        // Extract @[...] mention tokens before Markdown parsing (brackets are link syntax)
        $mentionMap = [];
        $text = preg_replace_callback('/@\[([^\]]+)\]/u', function ($match) use (&$mentionMap) {
            $placeholder = '%%MENTION_'.count($mentionMap).'%%';
            $mentionMap[$placeholder] = $match[1];

            return $placeholder;
        }, $text);

        $lines = explode("\n", $text);
        foreach ($lines as &$line) {
            if (str_starts_with(trim($line), '/')) {
                $line = '<code class="slash-command">'.e(trim($line)).'</code>';
            }
        }
        $text = implode("\n", $lines);

        $html = Str::markdown($text);

        $html = $this->replaceMentions($html, $mentionMap);

        return $this->decorateChecklistItems($html);
    }

    private function replaceMentions(string $html, array $mentionMap): string
    {
        if (empty($mentionMap)) {
            return $html;
        }

        // Strip title parenthetical to get bare names
        $namesByPlaceholder = [];
        foreach ($mentionMap as $placeholder => $token) {
            $namesByPlaceholder[$placeholder] = preg_replace('/\s*\([^)]*\)$/', '', trim($token));
        }

        $names = array_unique(array_values($namesByPlaceholder));
        $users = User::whereIn('name', $names)->get()->keyBy('name');

        foreach ($namesByPlaceholder as $placeholder => $name) {
            $user = $users[$name] ?? null;
            if ($user) {
                $replacement = '<a class="mention" href="/users/'.$user->id.'">@'.e($name).'</a>';
            } else {
                $replacement = '@'.e($name);
            }
            $html = str_replace($placeholder, $replacement, $html);
        }

        return $html;
    }

    private function wrapStackTrace(string $text): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $text) ?: [];

        $looksLikeTrace = count($lines) > 1
            && preg_match('/(?:Exception|Error|Stack trace)/', $lines[0]) === 1
            && collect($lines)->slice(1)->contains(fn (string $line) => preg_match('/^#\d+\s/', $line) === 1);

        if (! $looksLikeTrace) {
            return $text;
        }

        return "```\n{$text}\n```";
    }

    private function decorateChecklistItems(string $html): string
    {
        $html = str_replace('<li><input disabled="" type="checkbox">', '<li class="checklist-item"><input disabled type="checkbox">', $html);
        $html = str_replace('<li><input checked="" disabled="" type="checkbox">', '<li class="checklist-item"><input checked disabled type="checkbox">', $html);

        return $html;
    }
}
