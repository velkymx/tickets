<?php

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class KbMigrationsTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function kb_categories_table_exists_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('kb_categories'));
        $this->assertTrue(Schema::hasColumns('kb_categories', [
            'id', 'name', 'slug', 'description', 'sort_order', 'created_at', 'updated_at',
        ]));
    }

    #[Test]
    public function kb_tags_table_exists_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('kb_tags'));
        $this->assertTrue(Schema::hasColumns('kb_tags', [
            'id', 'name', 'slug', 'created_at', 'updated_at',
        ]));
    }

    #[Test]
    public function kb_articles_table_exists_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('kb_articles'));
        $this->assertTrue(Schema::hasColumns('kb_articles', [
            'id', 'title', 'slug', 'body_markdown', 'body_html',
            'category_id', 'user_id', 'owner_id',
            'status', 'visibility', 'reviewed_at', 'published_at',
            'created_at', 'updated_at', 'deleted_at',
        ]));
    }

    #[Test]
    public function kb_article_tag_pivot_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('kb_article_tag'));
        $this->assertTrue(Schema::hasColumns('kb_article_tag', ['article_id', 'tag_id']));
    }

    #[Test]
    public function kb_article_versions_table_exists_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('kb_article_versions'));
        $this->assertTrue(Schema::hasColumns('kb_article_versions', [
            'id', 'article_id', 'user_id', 'title', 'body_markdown', 'body_html',
            'commit_message', 'version_number', 'created_at', 'updated_at',
        ]));
    }

    #[Test]
    public function kb_article_permissions_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('kb_article_permissions'));
        $this->assertTrue(Schema::hasColumns('kb_article_permissions', [
            'id', 'article_id', 'user_id', 'created_at', 'updated_at',
        ]));
    }

    #[Test]
    public function kb_article_attachments_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('kb_article_attachments'));
        $this->assertTrue(Schema::hasColumns('kb_article_attachments', [
            'id', 'article_id', 'user_id', 'filename', 'path', 'mime_type', 'size',
            'created_at', 'updated_at',
        ]));
    }

    #[Test]
    public function users_table_has_kb_role_column(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'kb_role'));
    }
}
