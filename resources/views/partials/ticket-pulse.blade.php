<div class="d-lg-none mb-3">
    <button
        class="btn btn-outline-secondary w-100 d-flex justify-content-between align-items-center"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#ticket-pulse-panel"
        aria-expanded="false"
        aria-controls="ticket-pulse-panel"
    >
        <span class="fw-semibold">Pulse Summary</span>
        <span class="badge text-bg-dark" x-text="pulse.status"></span>
    </button>
</div>

<aside
    id="ticket-pulse-panel"
    class="collapse d-lg-block card shadow-sm border-0 mb-4 ticket-pulse-panel"
    style="position: sticky; top: 1rem;"
>
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
            <div>
                <div class="text-uppercase text-muted small fw-semibold">Ticket Pulse</div>
                <div
                    class="fs-4 fw-bold"
                    :class="{
                        'text-danger fw-bold': pulse.execution_state === 'BLOCKED',
                        'text-success': pulse.execution_state === 'ON TRACK',
                        'text-warning': pulse.execution_state === 'AT RISK',
                        'text-muted': pulse.execution_state === 'IDLE'
                    }"
                    x-text="pulse.execution_state"
                ></div>
            </div>

            <div class="d-flex align-items-center">
                <template x-for="viewer in pulse.viewers ?? []" :key="viewer.user_id">
                    <img
                        :src="viewer.avatar_url"
                        :title="viewer.name"
                        class="rounded-circle border border-white ms-n2"
                        style="width: 28px; height: 28px; object-fit: cover;"
                        alt="Viewer"
                    >
                </template>
            </div>
        </div>

        <div class="mb-3">
            <div class="text-uppercase text-muted small fw-semibold mb-1">Status</div>
            <div class="d-flex align-items-center gap-2">
                <span
                    class="fw-semibold"
                    :class="pulse.status === 'BLOCKED' ? 'text-danger fw-bold' : ''"
                    x-text="pulse.status"
                ></span>
                <template x-if="pulse.status === 'BLOCKED'">
                    <span class="text-muted">(blocked)</span>
                </template>
            </div>
        </div>

        <template x-if="pulse.latest_blocker">
            <div class="mb-3">
                <div class="text-uppercase text-muted small fw-semibold mb-1">Reason</div>
                <div class="d-flex justify-content-between gap-2 align-items-start">
                    <div>
                        <div class="text-danger fw-semibold" x-text="pulse.latest_blocker.body"></div>
                        <div class="small text-muted">
                            <span x-text="pulse.latest_blocker.author"></span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-danger btn-sm">Resolve</button>
                </div>
            </div>
        </template>

        <div class="mb-3">
            <div class="text-uppercase text-muted small fw-semibold mb-1">Owner</div>
            <div
                :class="{
                    'text-primary fw-bold': pulse.owner_label === 'You own this',
                    'text-warning fw-bold': pulse.owner_label === 'Unassigned'
                }"
                x-text="pulse.owner_label"
            ></div>
        </div>

        <div class="mb-3">
            <div class="text-uppercase text-muted small fw-semibold mb-1">Next Action</div>
            <div x-text="pulse.next_action.body"></div>
            <template x-if="pulse.next_action.assignee">
                <div class="small text-muted" x-text="pulse.next_action.assignee"></div>
            </template>
        </div>

        <div class="mb-3 p-3 rounded bg-success-subtle">
            <div class="text-uppercase text-muted small fw-semibold mb-1">Latest Decision</div>
            <template x-if="pulse.latest_decision">
                <div>
                    <div class="fw-semibold" x-text="pulse.latest_decision.body"></div>
                    <div class="small text-muted">
                        <span x-text="pulse.latest_decision.author"></span>
                    </div>
                    <template x-if="pulse.latest_decision.supersedes">
                        <div class="small text-muted">
                            Supersedes: <span x-text="pulse.latest_decision.supersedes"></span>
                        </div>
                    </template>
                    <div class="mt-2 d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm">View</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm">Update</button>
                    </div>
                </div>
            </template>
            <template x-if="!pulse.latest_decision">
                <div class="text-muted fst-italic">No decision recorded</div>
            </template>
        </div>

        <div class="mb-3">
            <div class="text-uppercase text-muted small fw-semibold mb-2">Open Threads</div>
            <template x-if="pulse.open_threads.length">
                <div class="d-flex flex-column gap-2">
                    <template x-for="thread in pulse.open_threads" :key="thread.id">
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <a
                                class="text-decoration-none"
                                :href="`#note_${thread.id}`"
                                x-text="`${thread.subject} (${thread.reply_count})`"
                            ></a>
                            <button type="button" class="btn btn-outline-secondary btn-sm">Resolve</button>
                        </div>
                    </template>
                </div>
            </template>
            <template x-if="!pulse.open_threads.length">
                <div class="text-muted fst-italic">No open threads</div>
            </template>
        </div>

        <div class="pt-3 border-top">
            <div class="text-uppercase text-muted small fw-semibold mb-1">Last Update</div>
            <template x-if="pulse.staleness_message">
                <div class="text-warning">
                    <i class="fas fa-clock me-1" aria-hidden="true"></i>
                    <span x-text="pulse.staleness_message"></span>
                </div>
            </template>
            <template x-if="!pulse.staleness_message">
                <div class="text-muted" x-text="pulse.last_activity_at ? new Date(pulse.last_activity_at).toLocaleString() : 'No activity yet'"></div>
            </template>
        </div>
    </div>
</aside>
