<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KbArticleVersion extends Model
{
    protected $fillable = [
        'article_id', 'user_id', 'title', 'body_markdown', 'body_html',
        'commit_message', 'version_number',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(KbArticle::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
