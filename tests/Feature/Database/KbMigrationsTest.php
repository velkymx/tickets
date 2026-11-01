<?php

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class KbMigrationsTest extends TestCase
{
    use SeedsDatabase;

    /**
     * @var array<string, array<int, string>>
     */
    private const TABLES = [
        'kb_categories' => ['id', 'name', 'slug', 'description', 'sort_order', 'created_at', 'updated_at'],
        'kb_tags' => ['id', 'name', 'slug', 'created_at', 'updated_at'],
        'kb_articles' => [
            'id', 'title', 'slug', 'body_markdown', 'body_html',
            'category_id', 'user_id', 'owner_id',
            'status', 'visibility', 'reviewed_at', 'published_at',
            'created_at', 'updated_at', 'deleted_at',
        ],
        'kb_article_tag' => ['article_id', 'tag_id'],
        'kb_article_versions' => [
            'id', 'article_id', 'user_id', 'title', 'body_markdown', 'body_html',
            'commit_message', 'version_number', 'created_at', 'updated_at',
        ],
        'kb_article_permissions' => ['id', 'article_id', 'user_id', 'created_at', 'updated_at'],
        'kb_article_attachments' => [
            'id', 'article_id', 'user_id', 'filename', 'path', 'mime_type', 'size',
            'created_at', 'updated_at',
        ],
    ];

    #[Test]
    public function kb_tables_exist_with_expected_columns(): void
    {
        foreach (self::TABLES as $table => $columns) {
            $this->assertTrue(Schema::hasTable($table), "Missing table {$table}");
            $this->assertTrue(Schema::hasColumns($table, $columns), "Missing columns on {$table}");
        }
    }

    #[Test]
    public function users_table_has_kb_role_column(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'kb_role'));
    }
}
