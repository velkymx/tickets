<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Automation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'trigger', 'enabled', 'stop_after_match', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'stop_after_match' => 'boolean',
        ];
    }

    public function rules(): HasMany
    {
        return $this->hasMany(AutomationRule::class)->orderBy('sort_order');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AutomationRun::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
