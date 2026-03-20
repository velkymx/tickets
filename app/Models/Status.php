<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    public $timestamps = false;

    public static function closedStatusIds(): array
    {
        return [5, 8, 9];
    }

    public static function isClosed(int $statusId): bool
    {
        return in_array($statusId, self::closedStatusIds());
    }
}
