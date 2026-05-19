<?php

namespace App\Automations\Contracts;

use App\Models\AutomationAction;
use App\Models\AutomationRun;

interface AutomationActionDriverInterface
{
    public function execute(AutomationAction $action, array $context, AutomationRun $run): void;
}
