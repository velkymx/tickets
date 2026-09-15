<?php

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

if (! function_exists('clean')) {
    /**
     * Sanitize untrusted HTML for {!! !!} output from the Markdown/Quill
     * pipelines. Uses Symfony's HTML sanitizer (allow-list of safe elements),
     * which strips event-handler attributes, javascript: URLs, <script>,
     * <input>, and other vectors that strip_tags cannot.
     */
    function clean(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        static $sanitizer = null;

        if ($sanitizer === null) {
            $config = (new HtmlSanitizerConfig())
                ->allowSafeElements()
                // Rendered task-list checkboxes only — no autofocus/on* vectors.
                ->allowElement('input', ['type', 'checked', 'disabled'])
                // Styling hooks emitted by the Markdown pipeline (slash-command,
                // mention, cross-reference badges, checklist items).
                ->allowAttribute('class', '*')
                ->allowRelativeLinks()
                ->allowLinkSchemes(['https', 'http', 'mailto'])
                ->allowMediaSchemes(['https', 'http']);

            $sanitizer = new HtmlSanitizer($config);
        }

        return $sanitizer->sanitize($html);
    }
}
