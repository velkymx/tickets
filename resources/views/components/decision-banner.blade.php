@props(['request'])

@php($urgency = $request->decisionUrgency())
<div class="alert alert-{{ $urgency }} border-{{ $urgency }} border-2 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2" role="alert" data-decision-banner data-decision-urgency="{{ $urgency }}">
    <div>
        <strong><i class="fa-solid fa-gavel me-1" aria-hidden="true"></i>Decision needed from {{ $request->decider?->name ?? 'Unknown' }}</strong>
        <div>{{ \Illuminate\Support\Str::of(strip_tags($request->body))->before("\n")->trim()->limit(200) }}</div>
        <div class="small">
            Asked by {{ $request->user?->name ?? 'Unknown' }},
            <span title="Waiting since {{ $request->created_at->format('M jS, Y g:ia') }}">waiting {{ $request->created_at->diffForHumans(null, true) }}</span>
        </div>
    </div>
    <button type="button" class="btn btn-{{ $urgency }} btn-sm flex-shrink-0" data-record-decision="{{ $request->id }}"
            onclick="(function(){var n=document.getElementById('note_{{ $request->id }}');var r=n&&n.querySelector('.reply-composer textarea');if(!r){return;}n.scrollIntoView({behavior:'smooth',block:'center'});if(!r.value.startsWith('/decision')){r.value='/decision '+r.value;}r.focus();})()">
        <i class="fas fa-check me-1" aria-hidden="true"></i>Record decision
    </button>
</div>
