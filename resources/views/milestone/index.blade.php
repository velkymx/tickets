@extends('layouts.app')

@section('title', 'Milestones List')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Milestones</h1>
        {{-- Replaced pull-right with d-flex utilities --}}
        <a href="/milestone/create" class="btn btn-sm btn-primary">Create Milestone</a>
    </div>
    <hr>

    {{-- Replaced table-striped with B5 table classes --}}
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th class="text-center">Tickets</th>
                    <th>Start</th>
                    <th>Due</th>
                    <th>Released</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                {{-- Active Milestones --}}
                @foreach ($milestones->where('end_at','') as $milestone)
                    <tr>
                        <td>{{ $milestone->name }}</td>
                        <td class="text-center">
                            {{-- Replaced old badge class with B5 badge class --}}
                            <span class="badge text-bg-info">
                                {{ $milestone->tickets()->whereNotIn('status_id',[5,8,9])->count() }} / {{ $milestone->tickets()->count() }}
                            </span>
                        </td>
                        
                        @if ($milestone->start_at && $milestone->start_at != '0000-00-00 00:00:00')
                            <td>{{ date('M jS, Y', strtotime($milestone->start_at)) }}</td>
                        @else
                            <td></td>
                        @endif

                        @if ($milestone->due_at && $milestone->due_at != '0000-00-00 00:00:00')
                            <td>{{ date('M jS, Y', strtotime($milestone->due_at)) }}</td>
                        @else
                            <td></td>
                        @endif

                        <td></td> {{-- Empty column for 'Released' since they are active --}}
                        
                        <td class="text-end text-nowrap">
                            {{-- Updated button classes for B5 --}}
                            <a href="/milestone/show/{{ $milestone->id }}" class="btn btn-sm btn-success">View</a> 
                            <a href="/milestone/print/{{ $milestone->id }}" class="btn btn-sm btn-secondary">Print</a> 
                            <a href="/milestone/edit/{{ $milestone->id }}" class="btn btn-sm btn-primary">Edit</a>
                        </td>
                    </tr>
                @endforeach
                
                {{-- Separator for Released Milestones --}}
                <tr class="table-active">
                    <td colspan="6" class="py-2">
                        <strong class="text-success"><i class="bi bi-check-circle-fill me-2"></i> Released Milestones</strong>
                    </td>
                </tr>
                
                {{-- Released Milestones (end_at > 0) --}}
                @foreach ($milestones->where('end_at','>','0') as $milestone)
                    <tr class="text-muted">
                        <td>{{ $milestone->name }}</td>
                        <td class="text-center">
                            {{-- Replaced old badge class with B5 badge class --}}
                            <span class="badge text-bg-secondary">
                                {{ $milestone->tickets()->whereNotIn('status_id',[5,8,9])->count() }} / {{ $milestone->tickets()->count() }}
                            </span>
                        </td>

                        @if ($milestone->start_at && $milestone->start_at != '0000-00-00 00:00:00')
                            <td>{{ date('M jS, Y', strtotime($milestone->start_at)) }}</td>
                        @else
                            <td></td>
                        @endif

                        @if ($milestone->due_at && $milestone->due_at != '0000-00-00 00:00:00')
                            <td>{{ date('M jS, Y', strtotime($milestone->due_at)) }}</td>
                        @else
                            <td></td>
                        @endif

                        @if ($milestone->end_at && $milestone->end_at != '0000-00-00 00:00:00')
                            <td>{{ date('M jS, Y', strtotime($milestone->end_at)) }}</td>
                        @else
                            <td></td>
                        @endif
                        
                        <td class="text-end text-nowrap">
                            <a href="/milestone/show/{{ $milestone->id }}" class="btn btn-sm btn-success">View</a> 
                            <a href="/milestone/print/{{ $milestone->id }}" class="btn btn-sm btn-secondary">Print</a> 
                            <a href="/milestone/edit/{{ $milestone->id }}" class="btn btn-sm btn-primary">Edit</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection