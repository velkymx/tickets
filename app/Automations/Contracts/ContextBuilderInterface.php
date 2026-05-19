<?php

namespace App\Automations\Contracts;

interface ContextBuilderInterface
{
    public function build(object $event): array;
}
