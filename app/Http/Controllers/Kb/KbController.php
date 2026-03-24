<?php

namespace App\Http\Controllers\Kb;

use App\Contracts\SearchableRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kb\StoreArticleRequest;
use App\Http\Requests\Kb\UpdateArticleRequest;
use App\Models\KbArticle;
use App\Models\KbArticlePermission;
use App\Models\KbCategory;
use App\Models\KbTag;
use App\Services\AttachmentService;
use App\Services\KbArticleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class KbController extends Controller
{
    public function __construct(
        private KbArticleService $articleService,
        private SearchableRepository $searchService,
    ) {}

    public function index()
    {
        $articles = $this->searchService->search('', Auth::user(), request()->only('category_id', 'status', 'tag_id'));
        $categories = KbCategory::orderBy('sort_order')->withCount('articles')->get();

        $layout = Auth::check() ? 'layouts.app' : 'layouts.kb-public';

        return view('kb.index', compact('articles', 'categories', 'layout'));
    }

    public function show(KbArticle $slug)
    {
        $article = $slug;
        $this->authorize('view', $article);

        $article->load(['category', 'tags', 'owner', 'creator']);
        $relatedArticles = KbArticle::where('id', '!=', $article->id)
            ->where('status', 'verified')
            ->whereHas('tags', fn ($q) => $q->whereIn('kb_tags.id', $article->tags->pluck('id')))
            ->limit(5)
            ->get();

        $layout = Auth::check() ? 'layouts.app' : 'layouts.kb-public';

        return view('kb.show', compact('article', 'relatedArticles', 'layout'));
    }

    public function create()
    {
        $this->authorize('create', KbArticle::class);

        $categories = KbCategory::orderBy('sort_order')->get();
        $tags = KbTag::orderBy('name')->get();

        return view('kb.create', compact('categories', 'tags'));
    }

    public function store(StoreArticleRequest $request)
    {
        $this->authorize('create', KbArticle::class);

        $article = $this->articleService->create($request->validated(), Auth::user());

        if (! empty($request->permitted_users) && $request->visibility === 'restricted') {
            foreach ($request->permitted_users as $userId) {
                KbArticlePermission::create(['article_id' => $article->id, 'user_id' => $userId]);
            }
        }

        return redirect("/kb/{$article->slug}")->with('success', 'Article created.');
    }

    public function edit(KbArticle $slug)
    {
        $article = $slug;
        $this->authorize('update', $article);

        $article->load(['tags', 'permissions']);
        $categories = KbCategory::orderBy('sort_order')->get();
        $tags = KbTag::orderBy('name')->get();

        return view('kb.edit', compact('article', 'categories', 'tags'));
    }

    public function update(UpdateArticleRequest $request, KbArticle $slug)
    {
        $article = $slug;
        $this->authorize('update', $article);

        $this->articleService->update($article, $request->validated(), Auth::user(), $request->commit_message);

        if ($request->visibility === 'restricted') {
            KbArticlePermission::where('article_id', $article->id)->delete();
            foreach ($request->permitted_users ?? [] as $userId) {
                KbArticlePermission::create(['article_id' => $article->id, 'user_id' => $userId]);
            }
        }

        return redirect("/kb/{$article->slug}")->with('success', 'Article updated.');
    }

    public function destroy(KbArticle $slug)
    {
        $article = $slug;
        $this->authorize('delete', $article);

        $article->delete();

        return redirect('/kb')->with('success', 'Article moved to trash.');
    }

    public function search(Request $request)
    {
        $query = $request->input('q', '');
        $articles = $this->searchService->search($query, Auth::user(), $request->only('category_id', 'status', 'tag_id'));

        $layout = Auth::check() ? 'layouts.app' : 'layouts.kb-public';

        return view('kb.search', compact('articles', 'query', 'layout'));
    }

    public function category(string $slug)
    {
        $category = KbCategory::where('slug', $slug)->firstOrFail();
        $articles = $this->searchService->search('', Auth::user(), ['category_id' => $category->id]);

        $layout = Auth::check() ? 'layouts.app' : 'layouts.kb-public';

        return view('kb.category', compact('category', 'articles', 'layout'));
    }

    public function tag(string $slug)
    {
        $tag = KbTag::where('slug', $slug)->firstOrFail();
        $articles = $this->searchService->search('', Auth::user(), ['tag_id' => $tag->id]);

        $layout = Auth::check() ? 'layouts.app' : 'layouts.kb-public';

        return view('kb.tag', compact('tag', 'articles', 'layout'));
    }

    public function uploadAttachment(Request $request, KbArticle $slug, AttachmentService $attachmentService)
    {
        $article = $slug;
        $this->authorize('update', $article);

        $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,svg,webp,pdf,md,txt,csv,zip',
        ]);

        if ($article->attachments()->count() >= 20) {
            return response()->json(['error' => 'Maximum 20 attachments per article.'], 422);
        }

        $data = $attachmentService->store($request->file('file'), "kb-attachments/{$article->id}");

        $attachment = $article->attachments()->create([
            'user_id' => Auth::id(),
            ...$data,
        ]);

        return response()->json([
            'id' => $attachment->id,
            'filename' => $attachment->filename,
            'url' => $attachment->url,
            'mime_type' => $attachment->mime_type,
            'isImage' => $attachment->is_image,
        ], 201);
    }

    public function deleteAttachment(KbArticle $slug, int $id, AttachmentService $attachmentService)
    {
        $article = $slug;
        $this->authorize('update', $article);

        $attachment = $article->attachments()->findOrFail($id);
        $attachmentService->delete($attachment->path);
        $attachment->delete();

        return response()->json(['message' => 'Attachment deleted.']);
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:kb_categories,name',
        ]);

        $category = KbCategory::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'sort_order' => 0,
        ]);

        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
        ]);
    }

    public function storeTag(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:kb_tags,name',
        ]);

        $tag = KbTag::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        return response()->json([
            'id' => $tag->id,
            'name' => $tag->name,
        ]);
    }
}
