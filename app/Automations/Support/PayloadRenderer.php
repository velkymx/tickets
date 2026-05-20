<?php

namespace App\Automations\Support;

use Mustache_Engine;

class PayloadRenderer
{
    private Mustache_Engine $mustache;

    public function __construct()
    {
        $this->mustache = new Mustache_Engine(['escape' => fn ($v) => $v]);
    }

    public function render(array $template, array $context): array
    {
        return $this->renderNode($template, $context);
    }

    private function renderNode(mixed $node, array $context): mixed
    {
        if (is_array($node)) {
            return array_map(fn ($v) => $this->renderNode($v, $context), $node);
        }

        if (is_string($node)) {
            return $this->mustache->render($node, $context);
        }

        return $node;
    }
}
