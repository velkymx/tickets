<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'automation_id', 'name', 'sort_order', 'conditions_json', 'continue_processing',
    ];

    protected function casts(): array
    {
        return [
            'conditions_json' => 'array',
            'continue_processing' => 'boolean',
        ];
    }

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(AutomationAction::class)->orderBy('sort_order');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AutomationRun::class);
    }
}
