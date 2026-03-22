{{-- Composer Help Modal --}}
<div class="modal fade" id="composerHelpModal" tabindex="-1" aria-labelledby="composerHelpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="composerHelpModalLabel">Composer Help</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Markdown Syntax</h6>
                <table class="table table-sm small mb-4">
                    <thead><tr><th>Syntax</th><th>Result</th></tr></thead>
                    <tbody>
                        <tr><td><code>**bold**</code></td><td><strong>bold</strong></td></tr>
                        <tr><td><code>*italic*</code></td><td><em>italic</em></td></tr>
                        <tr><td><code>`code`</code></td><td><code>code</code></td></tr>
                        <tr><td><code>```code block```</code></td><td>Code block</td></tr>
                        <tr><td><code>[link](url)</code></td><td>Hyperlink</td></tr>
                        <tr><td><code>- item</code></td><td>Bullet list</td></tr>
                        <tr><td><code>- [ ] task</code></td><td>Checklist</td></tr>
                        <tr><td><code>![alt](url)</code></td><td>Image</td></tr>
                    </tbody>
                </table>

                <h6>Slash Commands</h6>
                <table class="table table-sm small mb-4">
                    <thead><tr><th>Command</th><th>Description</th><th>Example</th></tr></thead>
                    <tbody>
                        <tr><td><code>/decision</code></td><td>Record a decision (immutable)</td><td><code>/decision We'll use Redis for caching</code></td></tr>
                        <tr><td><code>/blocker</code></td><td>Flag a blocker</td><td><code>/blocker Waiting on API key from vendor</code></td></tr>
                        <tr><td><code>/action</code></td><td>Assign an action item</td><td><code>/action @john Verify fix on staging</code></td></tr>
                        <tr><td><code>/assign</code></td><td>Assign the ticket</td><td><code>/assign @sarah</code></td></tr>
                        <tr><td><code>/status</code></td><td>Change ticket status</td><td><code>/status testing</code></td></tr>
                        <tr><td><code>/hours</code></td><td>Log time</td><td><code>/hours 2.5</code></td></tr>
                        <tr><td><code>/estimate</code></td><td>Set story points</td><td><code>/estimate 5</code></td></tr>
                        <tr><td><code>/close</code></td><td>Close the ticket</td><td><code>/close</code></td></tr>
                        <tr><td><code>/reopen</code></td><td>Reopen the ticket</td><td><code>/reopen</code></td></tr>
                        <tr><td><code>/pin</code></td><td>Pin this note</td><td><code>/pin</code></td></tr>
                    </tbody>
                </table>

                <h6>Mentions</h6>
                <p class="small">Type <code>@</code> followed by a username to mention someone. They will be notified.</p>

                <h6>Keyboard Shortcuts</h6>
                <table class="table table-sm small">
                    <tbody>
                        <tr><td><kbd>Cmd+Enter</kbd></td><td>Submit note</td></tr>
                        <tr><td><kbd>Escape</kbd></td><td>Close modals/cancel</td></tr>
                        <tr><td><kbd>?</kbd></td><td>Open this help dialog</td></tr>
                    </tbody>
                </table>

                <h6>Signal Types</h6>
                <ul class="small">
                    <li><strong class="text-success">Decision</strong> — Immutable record. Cannot be edited after creation. Use <code>/decision</code> to create.</li>
                    <li><strong class="text-danger">Blocker</strong> — Flags an issue blocking progress. Surfaces in Ticket Pulse. Use <code>/blocker</code>.</li>
                    <li><strong class="text-warning">Action</strong> — Assigns a task to someone. Must include an @mention. Use <code>/action @user task</code>.</li>
                    <li><strong class="text-info">Update</strong> — General status update. Use <code>/update</code>.</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
