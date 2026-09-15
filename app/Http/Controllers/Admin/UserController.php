<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->input('q', ''));

        $users = User::query()
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
            }))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'q'));
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'admin' => 'nullable|boolean',
            'active' => 'nullable|boolean',
            'kb_role' => 'nullable|in:author,admin',
        ]);

        // Guardrail: an admin cannot revoke their own admin rights or deactivate
        // themselves, which would lock them (and possibly everyone) out.
        $isSelf = $user->id === $request->user()->id;

        $user->admin = $isSelf ? true : $request->boolean('admin');
        $user->active = $isSelf ? true : $request->boolean('active');
        $user->kb_role = $validated['kb_role'] ?? null;
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('info_message', "Updated access for {$user->name}.");
    }
}
