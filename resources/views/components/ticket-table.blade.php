@php
    $showCheckbox = $showCheckbox ?? false;
    $showType = $showType ?? false;
    $showUpdated = $showUpdated ?? true;
    $small = $small ?? false;
    $sortable = $sortable ?? false;
    $paginator = $paginator ?? null;
    $emptyMessage = $emptyMessage ?? null;

    $colCount = 1 + ($showCheckbox ? 1 : 0) + ($showType ? 1 : 0) + 1 + 1 + 1 + 1 + 1 + ($showUpdated ? 1 : 0);
@endphp

<div class="table-responsive d-none d-md-block" data-pane="table">
    <table class="table table-striped table-hover align-middle ticket-table {{ $small ? 'table-sm' : '' }}">
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
                    <x-sort-header column="project_id" label="Project" class="d-none d-lg-table-cell" />
                @else
                    <th class="col-1">P</th>
                    <th class="col-2">Status</th>
                    <th class="col-2 d-none d-lg-table-cell">Project</th>
                @endif
                <th class="col-2">Assignee</th>
                <th class="col-1">Est</th>
                @if($showUpdated)
                    @if($sortable)
                        <x-sort-header column="updated_at" label="Updated" class="d-none d-lg-table-cell" />
                    @else
                        <th class="col-2 d-none d-lg-table-cell">Updated</th>
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
                    <td class="text-truncate" style="max-width: 280px;">
                        @php $noteCount = $tick->notes->where('hide', '0')->count(); @endphp
                        @if ($noteCount > 0)
                            <span class="badge text-bg-info float-end ms-1" title="{{ $noteCount }} notes">{{ $noteCount }}</span>
                        @endif
                        @if(!$showType)
                            <i class="{{ $tick->type->icon }} me-1" title="{{ $tick->type->name }}" aria-hidden="true"></i>
                        @endif
                        <a href="/tickets/{{ $tick->id }}" class="text-decoration-none text-body" title="#{{ $tick->id }} {{ $tick->subject }}">
                            #{{ $tick->id }} {{ $tick->subject }}
                        </a>
                    </td>
                    @if($showType)
                        <td><span class="badge text-bg-secondary">{{ $tick->type->name }}</span></td>
                    @endif
                    <td class="text-nowrap">
                        <x-importance-badge :importance="$tick->importance" />
                    </td>
                    <td class="text-nowrap"><span class="badge text-bg-secondary">{{ $tick->status->name }}</span></td>
                    <td class="text-truncate d-none d-lg-table-cell" style="max-width: 280px;">{{ $tick->project->name }}</td>
                    <td class="text-truncate" style="max-width: 280px;">
                        @if ($tick->assignee)
                            <a href="/users/{{ $tick->assignee->id }}" class="text-decoration-none text-body">{{ $tick->assignee->name }}</a>
                        @else
                            <span class="text-muted fst-italic">Unassigned</span>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        @php($points = (int) ($tick->storypoints ?? 0))
                        @if($points > 0)
                            <span class="badge text-bg-secondary">{{ $points }}SP</span>
                        @elseif($tick->estimate > 0)
                            <span class="badge text-bg-secondary">{{ rtrim(rtrim(number_format($tick->estimate, 2), '0'), '.') }}h</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    @if($showUpdated)
                        <td class="small text-muted text-nowrap d-none d-lg-table-cell">{{ $tick->updated_at->format('M jS, Y g:ia') }}</td>
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

<div class="d-md-none" data-pane="cards">
    @forelse ($tickets as $tick)
        <article class="card shadow-sm mb-2 ticket-card">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    @if($showCheckbox)
                        <input type="checkbox" name="tickets[{{ $tick->id }}]" value="{{ $tick->id }}" class="form-check-input mt-1">
                    @endif
                    <a href="/tickets/{{ $tick->id }}" class="text-decoration-none text-body fw-semibold flex-grow-1">
                        <span class="text-muted">#{{ $tick->id }}</span> {{ $tick->subject }}
                    </a>
                    <span class="badge text-bg-secondary text-nowrap">{{ $tick->status->name }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                    <span class="d-inline-flex align-items-center gap-2">
                        <x-importance-badge :importance="$tick->importance" />
                        <span>{{ $tick->assignee?->name ?? 'Unassigned' }}</span>
                    </span>
                    @if($showUpdated)
                        <span title="{{ $tick->updated_at->format('M jS, Y g:ia') }}">{{ $tick->updated_at->diffForHumans() }}</span>
                    @endif
                </div>
            </div>
        </article>
    @empty
        <p class="text-muted text-center py-4 mb-0">{{ $emptyMessage ?? 'No tickets found.' }}</p>
    @endforelse
</div>

@if($paginator)
    {!! $paginator->links('pagination::bootstrap-5') !!}
@endif
