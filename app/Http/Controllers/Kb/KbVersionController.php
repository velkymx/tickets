<?php

namespace App\Http\Controllers\Kb;

use App\Http\Controllers\Controller;
use App\Models\KbArticle;
use App\Services\KbArticleService;
use Illuminate\Support\Facades\Auth;

class KbVersionController extends Controller
{
    public function index(KbArticle $slug)
    {
        $article = $slug;
        $this->authorize('view', $article);

        $versions = $article->versions()->with('editor')->get();

        return view('kb.history.index', compact('article', 'versions'));
    }

    public function show(KbArticle $slug, int $version)
    {
        $article = $slug;
        $this->authorize('view', $article);

        $versionModel = $article->versions()->where('version_number', $version)->firstOrFail();

        return view('kb.history.show', compact('article', 'versionModel'));
    }

    public function diff(KbArticle $slug, int $from, int $to)
    {
        $article = $slug;
        $this->authorize('view', $article);

        $fromVersion = $article->versions()->where('version_number', $from)->firstOrFail();
        $toVersion = $article->versions()->where('version_number', $to)->firstOrFail();

        return view('kb.history.diff', compact('article', 'fromVersion', 'toVersion'));
    }

    public function restore(KbArticle $slug, int $version, KbArticleService $articleService)
    {
        $article = $slug;
        $this->authorize('update', $article);

        $versionModel = $article->versions()->where('version_number', $version)->firstOrFail();

        $articleService->update($article, [
            'title' => $versionModel->title,
            'body_markdown' => $versionModel->body_markdown,
            'category_id' => $article->category_id,
            'visibility' => $article->visibility,
            'tags' => $article->tags->pluck('id')->all(),
        ], Auth::user(), "Restored from version {$version}");

        return redirect("/kb/{$article->slug}")->with('success', "Restored to version {$version}.");
    }
}
