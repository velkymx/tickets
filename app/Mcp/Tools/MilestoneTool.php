<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\HasGuidance;
use App\Mcp\Concerns\ResolvesApiUser;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

abstract class MilestoneTool extends Tool
{
    use HasGuidance;
    use ResolvesApiUser;

    protected function milestoneNotFound(): Response
    {
        return Response::error('Milestone not found.');
    }

    protected function notAuthorized(): Response
    {
        return Response::error('You can only update a milestone you own or scrum-master.');
    }
}
