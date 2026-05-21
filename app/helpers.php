<?php

if (! function_exists('clean')) {
    /**
     * Strip unsafe HTML tags, keeping a safe allowlist for rich-text content.
     * Used in views that output {!! !!} raw HTML from Quill or Markdown pipelines.
     */
    function clean(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $allowed = implode('', [
            '<p>', '<br>', '<hr>',
            '<h1>', '<h2>', '<h3>', '<h4>', '<h5>', '<h6>',
            '<strong>', '<b>', '<em>', '<i>', '<u>', '<s>', '<del>',
            '<code>', '<pre>', '<blockquote>',
            '<ol>', '<ul>', '<li>',
            '<a>', '<img>',
            '<span>', '<div>',
            '<input>',
            '<table>', '<thead>', '<tbody>', '<tr>', '<th>', '<td>',
        ]);

        return strip_tags($html, $allowed);
    }
}
