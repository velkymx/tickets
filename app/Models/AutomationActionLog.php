<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationActionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'automation_run_id', 'automation_action_id', 'status', 'request_url',
        'request_payload', 'response_code', 'response_body', 'error_message',
        'attempts', 'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'executed_at' => 'datetime',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(AutomationRun::class, 'automation_run_id');
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(AutomationAction::class, 'automation_action_id');
    }
}
