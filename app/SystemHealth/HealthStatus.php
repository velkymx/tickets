<?php

namespace App\SystemHealth;

enum HealthStatus: string
{
    case Ok = 'OK';
    case Warning = 'Warning';
    case Problem = 'Problem';
    case Enabled = 'Enabled';
    case Disabled = 'Disabled';
    case Error = 'Error';
}
