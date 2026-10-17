<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSavedViewRequest;
use App\Models\SavedView;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class SavedViewController extends Controller
{
    public function store(StoreSavedViewRequest $request): RedirectResponse
    {
        Auth::user()->savedViews()->create($request->validated());

        return redirect()->back()->with('status', 'View saved.');
    }

    public function destroy(SavedView $savedView): RedirectResponse
    {
        $this->authorize('delete', $savedView);
        $savedView->delete();

        return redirect()->back()->with('status', 'View deleted.');
    }
}
