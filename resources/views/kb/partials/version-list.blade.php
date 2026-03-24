<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Version</th>
                <th>Commit Message</th>
                <th>Editor</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($versions as $version)
                <tr>
                    <td>
                        <a href="{{ route('kb.history.show', [$article->slug, $version->version_number]) }}">
                            v{{ $version->version_number }}
                        </a>
                    </td>
                    <td>{{ $version->commit_message }}</td>
                    <td>{{ $version->editor->name ?? 'Unknown' }}</td>
                    <td>{{ $version->created_at->format('M jS, Y g:ia') }}</td>
                    <td>
                        <a href="{{ route('kb.history.show', [$article->slug, $version->version_number]) }}" class="btn btn-sm btn-outline-secondary">View</a>
                        @if(!$loop->last)
                            <a href="{{ route('kb.diff', [$article->slug, $version->version_number - 1, $version->version_number]) }}" class="btn btn-sm btn-outline-primary">Diff</a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
