<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;

    public $timestamps = false;

    public static function closedStatusIds(): array
    {
        return self::whereIn('name', ['completed', 'duplicte', 'declined'])->pluck('id')->toArray();
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
