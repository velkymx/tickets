@auth
    @php($views = auth()->user()->savedViews)
    @if($views->isNotEmpty() || ! empty($searchQuery ?? ''))
        <div class="d-flex flex-wrap align-items-center gap-1 mb-2">
            @foreach($views as $view)
                <span class="badge {{ ($searchQuery ?? '') === $view->query ? 'text-bg-success' : 'text-bg-secondary' }} d-inline-flex align-items-center gap-1">
                    <a href="{{ request()->fullUrlWithQuery(['q' => $view->query, 'page' => null]) }}" class="text-reset text-decoration-none">{{ $view->name }}</a>
                    <form method="POST" action="{{ route('saved-views.destroy', $view) }}" class="d-inline" onsubmit="return confirm('Delete this view?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-link btn-sm p-0 text-reset" title="Delete view"><i class="fa-solid fa-xmark"></i></button>
                    </form>
                </span>
            @endforeach
            @if(! empty($searchQuery ?? ''))
                <form method="POST" action="{{ route('saved-views.store') }}" class="d-flex gap-1 ms-1">
                    @csrf
                    <input type="hidden" name="query" value="{{ $searchQuery }}">
                    <input type="text" name="name" class="form-control form-control-sm" style="width: 140px;" placeholder="Save view as..." required maxlength="100">
                    <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                </form>
            @endif
        </div>
    @endif
@endauth
