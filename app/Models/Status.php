<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    protected const CLOSED_STATUS_NAMES = [
        'completed',
        'duplicate',
        'declined',
        'closed',
        'resolved',
        'done',
    ];

    public static function closedStatusIds(): array
    {
        return cache()->rememberForever('closed_status_ids', function () {
            return self::query()
                ->whereIn(\DB::raw('LOWER(name)'), self::CLOSED_STATUS_NAMES)
                ->pluck('id')
                ->toArray();
        });
    }

    public static function activeStatusIds(): array
    {
        return self::whereNotIn('id', self::closedStatusIds())->pluck('id')->toArray();
    }

    public static function isClosed(int $statusId): bool
    {
        return in_array($statusId, self::closedStatusIds(), true);
    }
}
