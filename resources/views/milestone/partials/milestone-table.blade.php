@php($released = $released ?? false)

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th>Name</th>
                <th class="text-center">Tickets</th>
                <th>Start</th>
                <th>Due</th>
                @if ($released)
                    <th>Released</th>
                @endif
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($milestones as $milestone)
                <tr{!! $released ? ' class="text-muted"' : '' !!}>
                    <td>{{ $milestone->name }}</td>
                    <td class="text-center">
                        <span class="badge text-bg-{{ $released ? 'secondary' : 'info' }}">{{ $milestone->tickets_open }} / {{ $milestone->tickets_total }}</span>
                    </td>
                    <td>{{ $milestone->start_at ? $milestone->start_at->format('M jS, Y') : '' }}</td>
                    <td>{{ $milestone->due_at ? $milestone->due_at->format('M jS, Y') : '' }}</td>
                    @if ($released)
                        <td>{{ $milestone->end_at ? $milestone->end_at->format('M jS, Y') : '' }}</td>
                    @endif
                    <td class="text-end text-nowrap">
                        <a href="/milestone/show/{{ $milestone->id }}" class="btn btn-sm btn-success">View</a>
                        <a href="/milestone/print/{{ $milestone->id }}" class="btn btn-sm btn-secondary">Print</a>
                        <a href="/milestone/edit/{{ $milestone->id }}" class="btn btn-sm btn-primary">Edit</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $released ? 6 : 5 }}" class="text-center py-4">
                        <x-empty-state :message="$emptyMessage" />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $milestones->links('pagination::bootstrap-5') }}
