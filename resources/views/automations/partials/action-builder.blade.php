<div x-data="{
    actions: @json($actionsJson ?? []),

    addAction() {
        this.actions.push({
            type: 'call_webhook',
            config: {
                url: '',
                method: 'POST',
                headers: {},
                payload: {}
            }
        });
    },

    removeAction(index) {
        this.actions.splice(index, 1);
    },

    serialize() {
        return JSON.stringify(this.actions);
    }
}" x-init="$nextTick(() => $refs.actionsInput.value = serialize()); $watch('actions', () => $refs.actionsInput.value = serialize())">

    <input type="hidden" name="actions_json" x-ref="actionsInput" :value="serialize()">

    <template x-for="(action, index) in actions" :key="index">
        <div class="card border mb-2 p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-semibold small" x-text="'Action ' + (index + 1)"></span>
                <button type="button" class="btn btn-sm btn-outline-danger" @click="removeAction(index)">&times;</button>
            </div>

            <div class="mb-2">
                <label class="form-label small">Type</label>
                <select class="form-select form-select-sm" x-model="action.type">
                    @foreach($actionTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <template x-if="action.type === 'call_webhook' || action.type === 'call_api'">
                <div>
                    <div class="row g-2 mb-2">
                        <div class="col-2">
                            <label class="form-label small">Method</label>
                            <select class="form-select form-select-sm" x-model="action.config.method">
                                <option>POST</option>
                                <option>PUT</option>
                                <option>PATCH</option>
                                <option>DELETE</option>
                            </select>
                        </div>
                        <div class="col-10">
                            <label class="form-label small">URL</label>
                            <input type="text" class="form-control form-control-sm" x-model="action.config.url" placeholder="https://example.com/webhook">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">JSON Payload <span class="text-muted">(supports <code>@{{ ticket.id }}</code> templates)</span></label>
                        <textarea class="form-control form-control-sm font-monospace"
                                  rows="4"
                                  @input="try { action.config.payload = JSON.parse($event.target.value) } catch(e) {}"
                                  :value="JSON.stringify(action.config.payload ?? {}, null, 2)"
                                  placeholder='{"ticket_id": "@{{ ticket.id }}"}'></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Headers <span class="text-muted">(JSON)</span></label>
                        <textarea class="form-control form-control-sm font-monospace"
                                  rows="2"
                                  @input="try { action.config.headers = JSON.parse($event.target.value) } catch(e) {}"
                                  :value="JSON.stringify(action.config.headers ?? {}, null, 2)"
                                  placeholder='{"Authorization": "Bearer secret"}'></textarea>
                    </div>
                </div>
            </template>
        </div>
    </template>

    <button type="button" class="btn btn-sm btn-outline-primary mt-1" @click="addAction()">+ Add Action</button>
</div>
