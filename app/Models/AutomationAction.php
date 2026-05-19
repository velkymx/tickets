<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'automation_rule_id', 'type', 'sort_order', 'config_json',
    ];

    protected function casts(): array
    {
        return [
            'config_json' => 'array',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }
}
