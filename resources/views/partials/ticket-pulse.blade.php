<div class="d-lg-none mb-3">
    <button
        class="btn btn-outline-secondary w-100 d-flex justify-content-between align-items-center"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#ticket-pulse-panel"
        aria-expanded="false"
        aria-controls="ticket-pulse-panel"
    >
        <span class="fw-semibold">Ticket Pulse</span>
        <span class="badge text-bg-dark" x-text="pulse.execution_state"></span>
    </button>
</div>

<aside
    id="ticket-pulse-panel"
    class="collapse d-lg-block card shadow-sm mb-4"
    style="position: sticky; top: 1rem;"
>
    <div class="card-header bg-body-secondary d-flex justify-content-between align-items-center">
        Ticket Pulse
        <span
            class="badge"
            :class="{
                'text-bg-danger': pulse.execution_state === 'BLOCKED',
                'text-bg-success': pulse.execution_state === 'ON TRACK',
                'text-bg-warning': pulse.execution_state === 'AT RISK',
                'text-bg-secondary': pulse.execution_state === 'IDLE'
            }"
            x-text="pulse.execution_state"
        ></span>
    </div>
    <ul class="list-group list-group-flush">
        <template x-if="pulse.latest_blocker">
            <li class="list-group-item">
                <strong>Blocker:</strong>
                <span class="text-danger" x-text="pulse.latest_blocker.body"></span>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <span class="small text-muted" x-text="pulse.latest_blocker.author"></span>
                    <button type="button" class="btn btn-outline-danger btn-sm">Resolve</button>
                </div>
            </li>
        </template>

        <li class="list-group-item">
            <strong>Next Action:</strong>
            <span x-text="pulse.next_action.body"></span>
            <template x-if="pulse.next_action.assignee">
                <span class="small text-muted ms-1" x-text="pulse.next_action.assignee"></span>
            </template>
        </li>

        <li class="list-group-item">
            <strong>Latest Decision:</strong>
            <template x-if="pulse.latest_decision">
                <span>
                    <span x-text="pulse.latest_decision.body"></span>
                    <span class="small text-muted ms-1" x-text="pulse.latest_decision.author"></span>
                    <template x-if="pulse.latest_decision.supersedes">
                        <div class="small text-muted">
                            Supersedes: <span x-text="pulse.latest_decision.supersedes"></span>
                        </div>
                    </template>
                </span>
            </template>
            <template x-if="!pulse.latest_decision">
                <span class="text-muted fst-italic">No decision recorded</span>
            </template>
        </li>

        <li class="list-group-item">
            <strong>Open Threads:</strong>
            <template x-if="pulse.open_threads.length">
                <div class="mt-1 d-flex flex-column gap-1">
                    <template x-for="thread in pulse.open_threads" :key="thread.id">
                        <div class="d-flex justify-content-between align-items-center">
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
                <span class="text-muted fst-italic">None</span>
            </template>
        </li>
    </ul>
</aside>
