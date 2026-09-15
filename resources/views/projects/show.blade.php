@extends('layouts.app')
@section('title', $project->name . ' Ticket List')

@section('content')

{{-- Header and Edit Button --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">{{ $project->name }}</h1>
    <a href="/projects/edit/{{ $project->id }}" class="btn btn-sm btn-primary">Edit Project</a>
</div>

<div class="row">
    {{-- Main Content --}}
    <div class="col-lg-9">
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="tickets-tab" data-bs-toggle="tab" data-bs-target="#tickets"
                   type="button" role="tab" aria-controls="tickets" aria-selected="true">
                    Tickets <span class="badge text-bg-secondary ms-1">{{ $total }}</span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="matrix-tab" data-bs-toggle="tab" data-bs-target="#matrix"
                   type="button" role="tab" aria-controls="matrix" aria-selected="false">
                    Priority Matrix
                </a>
            </li>
        </ul>

        <div class="tab-content">
            {{-- Tab 1: Ticket Table --}}
            <div class="tab-pane fade show active" id="tickets" role="tabpanel" aria-labelledby="tickets-tab">
                <x-ticket-filters :viewfilters="$viewfilters" :filter="$filter" :action="url('projects/show/'.$project->id)" />
                <x-ticket-filter-tabs :counts="$tabCounts" />
                <x-ticket-table
                    :tickets="$tickets"
                    :paginator="$tickets"
                    :sortable="true"
                    :show-updated="true" />
            </div>

            {{-- Tab 2: Action Priority Matrix --}}
            <div class="tab-pane fade" id="matrix" role="tabpanel" aria-labelledby="matrix-tab">
                @include('projects.partials.priority-matrix', ['matrix' => $matrix])
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-3 mt-4 mt-lg-0">
        {{-- Progress --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-body-secondary">
                <strong>Progress</strong>
            </div>
            <div class="card-body">
                <h5 class="mb-2">{{ $percent }}% Complete</h5>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar bg-success"
                         role="progressbar"
                         style="width:{{ $percent }}%;"
                         aria-valuenow="{{ $percent }}"
                         aria-valuemin="0"
                         aria-valuemax="100">
                    </div>
                </div>
            </div>
        </div>

        {{-- Status Summary --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-body-secondary">
                <strong>Status Summary</strong>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    Total Tickets
                    <span class="badge text-bg-primary rounded-pill">{{ $project->tickets->count() }}</span>
                </li>
                @foreach ($statuscodes as $code)
                    @php $count = $project->tickets->where('status_id', $code->id)->count(); @endphp
                    @if($count > 0)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {{ $code->name }}
                            <span class="badge text-bg-secondary rounded-pill">{{ $count }}</span>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
