@extends('layouts.app')
@section('title', $milestone->name . ' Milestone')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>{{ $milestone->name }} Milestone</h1>
    {{-- Replaced pull-right with d-flex utilities and btn-group --}}
    <div class="btn-group" role="group" aria-label="Milestone Actions">
        <a href="/milestone/edit/{{ $milestone->id }}" class="btn btn-sm btn-primary">Edit Milestone</a>
        <a href="/milestone/print/{{ $milestone->id }}" class="btn btn-sm btn-secondary">Print</a>
    </div>
</div>

{{-- Status Info --}}
@if ($milestone->end_at && $milestone->end_at != '0000-00-00 00:00:00')
<p class="text-success">
    Started on {{ date('F jS, Y', strtotime($milestone->start_at)) }}, Released {{ date('F jS, Y', strtotime($milestone->end_at)) }}
</p>
@else
<p class="text-secondary">
    Unreleased Version - Started on {{ date('F jS, Y', strtotime($milestone->start_at)) }}
</p>
@endif
<hr class="mb-4">

<div class="row">
    {{-- Left Column: Tabs (9 columns) --}}
    <div class="col-lg-9">
 
        {{-- Bootstrap 5 Tab Navigation --}}
        {{-- NOTE: Tabs require the full Bootstrap JS bundle (or at least the 'tab' component JS) 
           to be included in layouts.app to switch panes. The markup is correct. --}}
        <ul class="nav nav-tabs mb-3" role="tablist">
            @php
                $i = 0;
                $available_status = [];
            @endphp
            
            {{-- Dynamic Status Tabs --}}
            @foreach ($statuscodes as $code_id => $code)
                @if (!in_array($code_id, [5, 8, 9]) && $milestone->tickets()->where('status_id', $code_id)->count() > 0)
                    @php
                        $available_status[$code_id] = $code['slug'];
                        $i++;
                        $active_class = ($i === 1) ? ' active' : '';
                        $ticket_count = $milestone->tickets()->where('status_id', $code_id)->count();
                    @endphp
                    <li class="nav-item" role="presentation">
                        <a class="nav-link{{ $active_class }}" 
                           id="{{ $code['slug'] }}-tab" 
                           data-bs-toggle="tab" 
                           data-bs-target="#{{ $code['slug'] }}" 
                           type="button" 
                           role="tab" 
                           aria-controls="{{ $code['slug'] }}" 
                           aria-selected="{{ $active_class ? 'true' : 'false' }}">
                            {{ $code['name'] }} <span class="badge text-bg-secondary ms-1">{{ $ticket_count }}</span>
                        </a>
                    </li>
                @endif
            @endforeach

            {{-- All Tickets Tab --}}
            <li class="nav-item" role="presentation">
                <a class="nav-link @if($i == 0) active @endif" 
                   id="all-tab" 
                   data-bs-toggle="tab" 
                   data-bs-target="#all" 
                   type="button" 
                   role="tab" 
                   aria-controls="all" 
                   aria-selected="@if($i == 0) true @else false @endif">
                    All Tickets <span class="badge text-bg-secondary ms-1">{{ $milestone->tickets->count() }}</span>
                </a>
            </li>
            
            {{-- Closed Tickets Tab --}}
            <li class="nav-item" role="presentation">
                <a class="nav-link" 
                   id="closed-tab" 
                   data-bs-toggle="tab" 
                   data-bs-target="#closed" 
                   type="button" 
                   role="tab" 
                   aria-controls="closed" 
                   aria-selected="false">
                    Closed <span class="badge text-bg-secondary ms-1">{{ $milestone->tickets->whereIn('status_id', ['5', '8', '9'])->count() }}</span>
                </a>
            </li>
        </ul>

        {{-- Tab Content --}}
        <div class="tab-content">
            @php $i = 0; @endphp
            @foreach($available_status as $st => $code)
                @php $i++; @endphp
                <div class="tab-pane fade @if($i == 1) show active @endif" id="{{ $code }}" role="tabpanel" aria-labelledby="{{ $code }}-tab">
                    <div class="table-responsive">
                        {{-- Replaced table-striped with B5 table classes --}}
                        <table class="table table-hover table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Title</th>        
                                    <th>P</th>
                                    <th>Status</th>
                                    <th>Project</th>
                                    <th>Assignee</th>
                                    <th>Est</th>
                                    <th>Notes</th>        
                                    <th>Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($milestone->tickets->where('status_id', $st)->sortByDesc('importance_id') as $tick)
                                <tr>
                                    <td class="text-{{ $tick->importance->class }}"><i class="{{ $tick->type->icon }}" title="{{ $tick->type->name }}"></i> <a href="/tickets/{{ $tick->id }}" class="text-decoration-none text-{{ $tick->importance->class }}">#{{ $tick->id }} {{ $tick->subject }}</a></td>        
                                    <td><span class="text-{{ $tick->importance->class }}" title="Priority: {{ $tick->importance->name }}"><i class="{{ $tick->importance->icon }}"></i></span></td>
                                    <td><span class="badge text-bg-light border text-secondary">{{ $tick->status->name }}</span></td>
                                    <td>{{ $tick->project->name }}</td>
                                    <td>{{ $tick->assignee->name }}</td>
                                    <td><span class="badge text-bg-secondary">{{ $tick->storypoints }}SP</span></td>
                                    <td>
                                        @if ($tick->notes()->where('hide', '0')->where('notetype', 'message')->count() > 0)
                                            <span class="badge text-bg-info">{{ $tick->notes()->where('hide', '0')->where('notetype', 'message')->count() }}</span>
                                        @endif
                                    </td>        
                                    <td class="small text-muted">{{ date('M jS, Y g:ia', strtotime($tick->updated_at)) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
          
            {{-- All Tickets Content --}}
            <div class="tab-pane fade @if($i == 0) show active @endif" id="all" role="tabpanel" aria-labelledby="all-tab">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th>        
                                <th>P</th>
                                <th>Status</th>
                                <th>Project</th>
                                <th>Assignee</th>
                                <th>Est</th>
                                <th>Notes</th>        
                                <th>Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($milestone->tickets->sortByDesc('importance_id') as $tick)
                            <tr>
                                <td class="text-{{ $tick->importance->class }}"><i class="{{ $tick->type->icon }}" title="{{ $tick->type->name }}"></i> <a href="/tickets/{{ $tick->id }}" class="text-decoration-none text-{{ $tick->importance->class }}">#{{ $tick->id }} {{ $tick->subject }}</a></td>        
                                <td><span class="text-{{ $tick->importance->class }}" title="Priority: {{ $tick->importance->name }}"><i class="{{ $tick->importance->icon }}"></i></span></td>
                                <td><span class="badge text-bg-light border text-secondary">{{ $tick->status->name }}</span></td>
                                <td>{{ $tick->project->name }}</td>
                                <td>{{ $tick->assignee->name }}</td>
                                <td><span class="badge text-bg-secondary">{{ $tick->storypoints }}SP</span></td>
                                <td>
                                    @if ($tick->notes()->where('hide', '0')->where('notetype', 'message')->count() > 0)
                                        <span class="badge text-bg-info">{{ $tick->notes()->where('hide', '0')->where('notetype', 'message')->count() }}</span>
                                    @endif
                                </td>        
                                <td class="small text-muted">{{ date('M jS, Y g:ia', strtotime($tick->updated_at)) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Closed Tickets Content --}}
            <div class="tab-pane fade" id="closed" role="tabpanel" aria-labelledby="closed-tab">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th>        
                                <th>P</th>
                                <th>Status</th>
                                <th>Project</th>
                                <th>Assignee</th>
                                <th>Est</th>
                                <th>Notes</th>        
                                <th>Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($milestone->tickets->whereIn('status_id', ['5', '8', '9'])->sortByDesc('importance_id') as $tick)
                            <tr>
                                <td class="text-{{ $tick->importance->class }}"><i class="{{ $tick->type->icon }}" title="{{ $tick->type->name }}"></i> <a href="/tickets/{{ $tick->id }}" class="text-decoration-none text-{{ $tick->importance->class }}">#{{ $tick->id }} {{ $tick->subject }}</a></td>        
                                <td><span class="text-{{ $tick->importance->class }}" title="Priority: {{ $tick->importance->name }}"><i class="{{ $tick->importance->icon }}"></i></span></td>
                                <td><span class="badge text-bg-light border text-secondary">{{ $tick->status->name }}</span></td>
                                <td>{{ $tick->project->name }}</td>
                                <td>{{ $tick->assignee->name }}</td>
                                <td><span class="badge text-bg-secondary">{{ $tick->storypoints }}SP</span></td>
                                <td>
                                    @if ($tick->notes()->where('hide', '0')->where('notetype', 'message')->count() > 0)
                                        <span class="badge text-bg-info">{{ $tick->notes()->where('hide', '0')->where('notetype', 'message')->count() }}</span>
                                    @endif
                                </td>        
                                <td class="small text-muted">{{ date('M jS, Y g:ia', strtotime($tick->updated_at)) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Right Column: Summary Sidebar (3 columns) --}}
    <div class="col-lg-3">
        {{-- Replaced list-group structure with B5 cards for better grouping --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                Team Roles
            </div>
            <ul class="list-group list-group-flush">
                @if ($milestone->owner)
                    <li class="list-group-item"><strong>Product Owner:</strong> {{ $milestone->owner->name }}</li>
                @endif
                @if ($milestone->scrummaster)
                    <li class="list-group-item"><strong>Scrum Master:</strong> {{ $milestone->scrummaster->name }}</li>
                @endif
            </ul>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                Team Members
            </div>
            <ul class="list-group list-group-flush">
                @php $mem = []; @endphp
                @foreach ($milestone->tickets as $tick)
                    @if (!in_array($tick->assignee->name, $mem))
                        @php $mem[] = $tick->assignee->name; @endphp
                        <li class="list-group-item">
                            <a href="/users/{{ $tick->assignee->id }}" class="text-decoration-none text-body">
                                <i class="fas fa-user me-2"></i> {{ $tick->assignee->name }}
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                Sprint Summary
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item">Total Tickets: <span class="badge text-bg-primary">{{ $milestone->tickets->count() }}</span></li>
                <li class="list-group-item">Est. Effort: <span class="badge text-bg-primary">{{ $milestone->tickets->sum('storypoints') }} SP</span></li>
                <li class="list-group-item">Est. Time: <span class="badge text-bg-primary">{{ $milestone->tickets->sum('estimate') }} Hrs</span></li>
                <li class="list-group-item text-success">
                    <strong>Progress: {{ $percent }}% Complete</strong>
                    {{-- Replaced old progress structure with B5 --}}
                    <div class="progress mt-2" style="height: 10px;">
                        <div class="progress-bar bg-success" 
                             role="progressbar" 
                             style="width:{{ $percent }}%;" 
                             aria-valuenow="{{ $percent }}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>
                </li>
                <li class="list-group-item">Closed Tickets: <span class="badge text-bg-success">{{ $milestone->tickets()->whereIn('status_id', [5, 9])->count() }}</span></li>
                <li class="list-group-item">Actual Time: <span class="badge text-bg-info">{{ $milestone->tickets->sum('actual') }} Hrs</span></li>
            </ul>
        </div>
    </div>
</div>
@endsection