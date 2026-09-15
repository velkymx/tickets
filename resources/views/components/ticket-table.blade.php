@php
    $showCheckbox = $showCheckbox ?? false;
    $showType = $showType ?? false;
    $showEstimate = $showEstimate ?? false;
    $showUpdated = $showUpdated ?? true;
    $small = $small ?? false;
    $sortable = $sortable ?? false;
    $paginator = $paginator ?? null;
    $emptyMessage = $emptyMessage ?? null;

    $colCount = 1 + ($showCheckbox ? 1 : 0) + ($showType ? 1 : 0) + 1 + 1 + 1 + 1 + ($showEstimate ? 1 : 0) + 1 + ($showUpdated ? 1 : 0);
@endphp

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle {{ $small ? 'table-sm' : '' }}">
        <thead>
            <tr>
                @if($showCheckbox)
                    <th class="col-auto">
                        <input type="checkbox" class="form-check-input" id="selectAll">
                    </th>
                @endif
                @if($sortable)
                    <x-sort-header column="subject" label="Title" />
                @else
                    <th class="text-nowrap">Title</th>
                @endif
                @if($showType)
                    <th class="col-1">T</th>
                @endif
                @if($sortable)
                    <x-sort-header column="importance_id" label="P" />
                    <x-sort-header column="status_id" label="Status" />
                    <x-sort-header column="project_id" label="Project" />
                @else
                    <th class="col-1">P</th>
                    <th class="col-2">Status</th>
                    <th class="col-2">Project</th>
                @endif
                <th class="col-2">Assignee</th>
                @if($showEstimate)
                    <th class="col-1">Est</th>
                @endif
                <th class="col-1">Notes</th>
                @if($showUpdated)
                    @if($sortable)
                        <x-sort-header column="updated_at" label="Updated" />
                    @else
                        <th class="col-2">Updated</th>
                    @endif
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($tickets as $tick)
                <tr>
                    @if($showCheckbox)
                        <td>
                            <input type="checkbox" name="tickets[{{ $tick->id }}]" value="{{ $tick->id }}" class="form-check-input">
                        </td>
                    @endif
                    <td class="text-{{ $tick->importance->class }}">
                        @if(!$showType)
                            <i class="{{ $tick->type->icon }} me-1" title="{{ $tick->type->name }}" aria-hidden="true"></i>
                        @endif
                        <a href="/tickets/{{ $tick->id }}" class="text-decoration-none text-{{ $tick->importance->class }}">
                            #{{ $tick->id }} {{ $tick->subject }}
                        </a>
                    </td>
                    @if($showType)
                        <td><span class="badge text-bg-secondary">{{ $tick->type->name }}</span></td>
                    @endif
                    <td>
                        <span class="text-{{ $tick->importance->class }}" title="Priority: {{ $tick->importance->name }}">
                            <i class="{{ $tick->importance->icon }}" aria-hidden="true"></i>
                        </span>
                    </td>
                    <td><span class="badge text-bg-secondary">{{ $tick->status->name }}</span></td>
                    <td>{{ $tick->project->name }}</td>
                    <td><a href="/users/{{ $tick->assignee->id }}" class="text-decoration-none text-body">{{ $tick->assignee->name }}</a></td>
                    @if($showEstimate)
                        <td><span class="badge text-bg-secondary">{{ $tick->storypoints }}SP</span></td>
                    @endif
                    <td>
                        @php $noteCount = $tick->notes->where('hide', '0')->count(); @endphp
                        @if ($noteCount > 0)
                            <span class="badge text-bg-info">{{ $noteCount }}</span>
                        @endif
                    </td>
                    @if($showUpdated)
                        <td class="small text-muted">{{ $tick->updated_at->format('M jS, Y g:ia') }}</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $colCount }}" class="text-center p-4">
                        <p class="text-muted mb-0">{{ $emptyMessage ?? 'No tickets found.' }}</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($paginator)
    {!! $paginator->links('pagination::bootstrap-5') !!}
@endif
