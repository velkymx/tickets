@extends('layouts.app')

@section('title', 'Milestones List')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Milestones</h1>
        <a href="/milestone/create" class="btn btn-sm btn-primary">Create Milestone</a>
    </div>

    <div class="table-responsive">
        @include('milestone.partials.milestone-table', ['milestones' => $active, 'released' => false, 'emptyMessage' => 'No active milestones.'])
    </div>

    @if ($released->isNotEmpty())
        <div class="mt-4 mb-2">
            <strong class="text-success"><i class="bi bi-check-circle-fill me-2"></i> Released Milestones</strong>
        </div>

        @include('milestone.partials.milestone-table', ['milestones' => $released, 'released' => true, 'emptyMessage' => 'No released milestones.'])
    @endif
@endsection
