<?php

namespace App\Http\Controllers\Kb;

use App\Http\Controllers\Controller;
use App\Models\KbArticle;
use App\Models\KbCategory;
use App\Models\KbTag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KbAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! $request->user()->isKbAdmin()) {
                abort(403);
            }

            return $next($request);
        });
    }

    public function categories()
    {
        $categories = KbCategory::orderBy('sort_order')->withCount('articles')->get();

        return view('kb.admin.categories', compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        KbCategory::create([
            ...$validated,
            'slug' => Str::slug($validated['name']),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()->back()->with('success', 'Category created.');
    }

    public function updateCategory(Request $request, int $id)
    {
        $category = KbCategory::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $category->update([
            ...$validated,
            'slug' => Str::slug($validated['name']),
        ]);

        return redirect()->back()->with('success', 'Category updated.');
    }

    public function destroyCategory(int $id)
    {
        $category = KbCategory::findOrFail($id);

        if ($category->articles()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete category with articles.');
        }

        $category->delete();

        return redirect()->back()->with('success', 'Category deleted.');
    }

    public function tags()
    {
        $tags = KbTag::orderBy('name')->withCount('articles')->get();

        return view('kb.admin.tags', compact('tags'));
    }

    public function storeTag(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|max:255']);

        KbTag::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        return redirect()->back()->with('success', 'Tag created.');
    }

    public function updateTag(Request $request, int $id)
    {
        $tag = KbTag::findOrFail($id);
        $validated = $request->validate(['name' => 'required|string|max:255']);

        $tag->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        return redirect()->back()->with('success', 'Tag updated.');
    }

    public function destroyTag(int $id)
    {
        KbTag::findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Tag deleted.');
    }

    public function trashed()
    {
        $articles = KbArticle::onlyTrashed()->with(['category', 'owner'])->paginate(15);

        return view('kb.admin.trashed', compact('articles'));
    }

    public function restoreArticle(int $id)
    {
        KbArticle::onlyTrashed()->findOrFail($id)->restore();

        return redirect()->back()->with('success', 'Article restored.');
    }
}
