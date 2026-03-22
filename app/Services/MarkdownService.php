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

        $lines = explode("\n", $text);
        foreach ($lines as &$line) {
            if (str_starts_with(trim($line), '/')) {
                $line = '<code class="slash-command">'.e(trim($line)).'</code>';
            }
        }
        $text = implode("\n", $lines);

        $html = Str::markdown($text);

        $html = $this->replaceMentions($html);

        return $this->decorateChecklistItems($html);
    }

    private function replaceMentions(string $html): string
    {
        preg_match_all('/@([\w\.]+)/', $html, $matches);

        if (empty($matches[1])) {
            return $html;
        }

        $usernames = array_unique($matches[1]);
        $users = User::whereIn('name', $usernames)->get()->keyBy('name');

        return preg_replace_callback('/@([\w\.]+)/', function ($matches) use ($users) {
            $username = $matches[1];
            $user = $users[$username] ?? null;

            if ($user) {
                return '<a class="mention" href="/users/'.$user->id.'">@'.$username.'</a>';
            }

            return $matches[0];
        }, $html);
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
