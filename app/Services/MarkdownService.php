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

        // 1. Convert slash commands to code blocks before Markdown parsing
        // This prevents them from being messed up by Markdown, and adds styling
        $lines = explode("\n", $text);
        foreach ($lines as &$line) {
            if (str_starts_with(trim($line), '/')) {
                $line = '<code class="slash-command">' . e(trim($line)) . '</code>';
            }
        }
        $text = implode("\n", $lines);

        // 2. Parse standard Markdown
        $html = Str::markdown($text);

        // 3. Parse @mentions
        // Regex looks for @username, where username matches User::name rules (assuming alphanumeric + spaces/dots)
        // Adjust regex based on username validation rules
        $html = preg_replace_callback('/@([\w\.]+)/', function ($matches) {
            $username = $matches[1];
            $user = User::where('name', $username)->first();
            
            if ($user) {
                return '<a href="/users/' . $user->id . '">@' . $username . '</a>';
            }
            
            return $matches[0];
        }, $html);

        return $html;
    }
}
