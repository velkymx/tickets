{{-- Reaction Bar — two inline emoji buttons --}}
@php
    $grouped = $note->groupedReactions();
    $emojis = ['thumbsup' => '👍', 'eyes' => '👀'];
@endphp
<div class="reaction-bar d-inline-flex gap-1">
    @foreach($emojis as $key => $icon)
        @if(isset($grouped[$key]))
            <form method="POST" action="/notes/{{ $note->id }}/react" class="d-inline reaction-toggle-form" data-note-id="{{ $note->id }}" data-emoji="{{ $key }}">
                @csrf
                <input type="hidden" name="emoji" value="{{ $key }}">
                <button type="submit" class="btn btn-sm rounded-pill {{ $grouped[$key]['reacted'] ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ $icon }} {{ $grouped[$key]['count'] }}
                </button>
            </form>
        @else
            <form method="POST" action="/notes/{{ $note->id }}/react" class="d-inline add-reaction reaction-toggle-form" data-note-id="{{ $note->id }}" data-emoji="{{ $key }}">
                @csrf
                <input type="hidden" name="emoji" value="{{ $key }}">
                <button type="submit" class="btn btn-sm rounded-pill btn-outline-secondary opacity-50">
                    {{ $icon }}
                </button>
            </form>
        @endif
    @endforeach
</div>
