<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KbArticlePermission extends Model
{
    protected $fillable = ['article_id', 'user_id'];

    public function article(): BelongsTo
    {
        return $this->belongsTo(KbArticle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
