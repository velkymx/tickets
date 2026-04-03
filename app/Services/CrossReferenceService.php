<?php

namespace App\Services;

class CrossReferenceService
{
    public function resolve(string $html): string
    {
        // Skip content inside <code>, <pre>, and <a> tags
        // Process only text nodes outside these elements
        return preg_replace_callback(
            '/(<(?:code|pre|a)[^>]*>.*?<\/(?:code|pre|a)>)|(?:(?<=\s|^|>)#(\d+)\b)|(?:\bkb:([a-z0-9-]+)\b)/s',
            function ($matches) {
                // If it's inside code/pre/a, return unchanged
                if (! empty($matches[1])) {
                    return $matches[1];
                }

                // Ticket reference
                if (! empty($matches[2])) {
                    $id = $matches[2];

                    return '<a class="badge bg-secondary text-decoration-none" href="/tickets/'.$id.'">#'.$id.'</a>';
                }

                // KB reference
                if (! empty($matches[3])) {
                    $slug = $matches[3];

                    return '<a class="badge bg-info text-decoration-none" href="/kb/'.$slug.'">kb:'.$slug.'</a>';
                }

                return $matches[0];
            },
            $html
        );
    }
}
