<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kb_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body_markdown');
            $table->longText('body_html');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('owner_id');
            $table->enum('status', ['draft', 'verified', 'deprecated'])->default('draft');
            $table->enum('visibility', ['public', 'internal', 'restricted'])->default('internal');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')->references('id')->on('kb_categories')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');

            $table->index('status');
            $table->index('visibility');
            $table->index('category_id');
            $table->index('owner_id');
        });

        // Add FULLTEXT index for MySQL search (ignored by SQLite)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE kb_articles ADD FULLTEXT fulltext_search (title, body_markdown)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kb_articles');
    }
};
