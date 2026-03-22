@props(['ticket', 'pulse'])

<div 
    x-data="{ 
        pulse: @js($pulse),
        refresh() {
            fetch(`/tickets/${this.pulse.id}/pulse`)
                .then(res => res.json())
                .then(data => {
                    this.pulse = data;
                });
        }
    }"
    x-init="refresh(); setInterval(() => refresh(), 30000)"
    class="card mb-3 shadow-sm border-0"
>
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <span class="badge" 
                      :class="{
                          'bg-danger': pulse.status === 'BLOCKED',
                          'bg-success': pulse.execution_state === 'ON TRACK',
                          'bg-secondary': pulse.execution_state === 'IDLE',
                          'bg-warning': pulse.status !== 'BLOCKED' && pulse.execution_state !== 'ON TRACK' && pulse.execution_state !== 'IDLE'
                      }"
                      x-text="pulse.status"></span>
                
                <span class="ms-2 text-muted small" x-text="pulse.owner_label"></span>
            </div>
            
            <div class="d-flex align-items-center">
                <template x-for="viewer in pulse.viewers" :key="viewer.user_id">
                    <img :src="viewer.avatar_url" 
                         :title="viewer.name"
                         class="rounded-circle border border-white ms-n2" 
                         style="width: 24px; height: 24px; object-fit: cover;"
                         alt="Viewer">
                </template>
            </div>
        </div>

        <template x-if="pulse.is_blocked">
            <div class="alert alert-danger py-2 px-3 mb-2 d-flex align-items-center">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <div>
                    <strong>Blocked:</strong>
                    <span x-text="pulse.blocker_reason"></span>
                </div>
            </div>
        </template>

        <div class="row g-2 mt-1">
            <div class="col-6">
                <div class="p-2 bg-light rounded h-100">
                    <div class="text-uppercase text-muted x-small fw-bold mb-1" style="font-size: 0.7rem;">Next Action</div>
                    <template x-if="pulse.next_action">
                        <div>
                            <div x-text="pulse.next_action.body" class="text-truncate"></div>
                            <small class="text-muted" x-text="new Date(pulse.next_action.created_at).toLocaleDateString()"></small>
                        </div>
                    </template>
                    <template x-if="!pulse.next_action">
                        <span class="text-muted fst-italic">None</span>
                    </template>
                </div>
            </div>
            
            <div class="col-6">
                <div class="p-2 bg-light rounded h-100">
                    <div class="text-uppercase text-muted x-small fw-bold mb-1" style="font-size: 0.7rem;">Latest Decision</div>
                    <template x-if="pulse.latest_decision">
                        <div>
                            <div x-text="pulse.latest_decision.body" class="text-truncate"></div>
                            <small class="text-muted" x-text="new Date(pulse.latest_decision.created_at).toLocaleDateString()"></small>
                        </div>
                    </template>
                    <template x-if="!pulse.latest_decision">
                        <span class="text-muted fst-italic">None</span>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
