@extends('layouts.app')
@section('title', 'User Tickets')
@section('content')
<div class="mb-4">
    <h1>{{ $user->name }}</h1>
    @if ($user->title)
    <p class="lead text-muted">{{ $user->title }}</p>
    @endif
    
    <div class="row g-2 mb-3 small">
        @if ($user->email)
        <div class="col-md-auto">
            <strong>Email:</strong> <a href="mailto:{{ $user->email }}" class="text-decoration-none">{{ $user->email }}</a>
        </div>
        @endif
        @if ($user->phone)
        <div class="col-md-auto">
            <strong>Phone Number:</strong> <a href="tel:{{ $user->phone }}" class="text-decoration-none">{{ $user->phone }}</a>
        </div>
        @endif
        @if ($currenttime)
        <div class="col-md-auto">
            <strong>Local time:</strong> {{ $currenttime }}
        </div>
        @endif
    </div>

    @if ($user->bio)
    <div class="card card-body bg-light mb-4">
        {!! $user->bio !!}
    </div>
    @endif
</div>

<hr class="mb-4" />

@foreach ($alltickets as $label => $tickets)
    @if ($tickets->isNotEmpty())
        <h3 class="mb-3 mt-4">{{ ucwords($label) }}</h3>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Title</th>
                        <th>T</th>
                        <th>P</th>
                        <th>Status</th>
                        <th>Project</th>
                        <th>Assignee</th>
                        <th style="width: 80px;">Notes</th>
                        <th style="width: 150px;">Created</th>
                        <th style="width: 150px;">Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tickets as $tick)
                    <tr>
                        <td><a href="/tickets/{{ $tick->id }}" class="text-decoration-none text-body">#{{ $tick->id }} {{ $tick->subject }}</a></td>
                        <td><span class="badge text-bg-light border text-secondary">{{ $tick->type->name }}</span></td>
                        <td><span class="badge text-bg-light border text-secondary">{{ $tick->importance->name }}</span></td>
                        <td><span class="badge text-bg-secondary">{{ $tick->status->name }}</span></td>
                        <td>{{ $tick->project->name }}</td>
                        <td>{{ $tick->assignee->name }}</td>
                        <td>
                            @if ($tick->notes()->where('hide','0')->count() > 0)
                                <span class="badge text-bg-info">{{ $tick->notes()->where('hide','0')->count() }}</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ date('M jS, Y g:ia',strtotime($tick->created_at)) }}</td>
                        <td class="small text-muted">{{ date('M jS, Y g:ia',strtotime($tick->updated_at)) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endforeach

@stop