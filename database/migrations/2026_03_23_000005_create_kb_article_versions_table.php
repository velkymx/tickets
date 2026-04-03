<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kb_article_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('article_id');
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->longText('body_markdown');
            $table->longText('body_html');
            $table->string('commit_message');
            $table->unsignedInteger('version_number');
            $table->timestamps();

            $table->foreign('article_id')->references('id')->on('kb_articles')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['article_id', 'version_number']);
            $table->index('article_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kb_article_versions');
    }
};
