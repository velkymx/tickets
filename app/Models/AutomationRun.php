<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'automation_id', 'automation_rule_id', 'trigger_name', 'status', 'context_json',
        'started_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'context_json' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }

    public function actionLogs(): HasMany
    {
        return $this->hasMany(AutomationActionLog::class);
    }
}
