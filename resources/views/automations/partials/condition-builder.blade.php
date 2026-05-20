<div x-data="{
    root: @json($conditionsJson ?? ['all' => []]),

    isGroup(item) {
        return item.all !== undefined || item.any !== undefined;
    },

    getChildren(item) {
        return item.all !== undefined ? item.all : item.any;
    },

    getType(item) {
        return item.all !== undefined ? 'all' : 'any';
    },

    toggleType(item) {
        if (item.all !== undefined) {
            item.any = item.all;
            delete item.all;
        } else {
            item.all = item.any;
            delete item.any;
        }
    },

    addLeaf(parent) {
        const arr = parent.all !== undefined ? parent.all : parent.any;
        arr.push({ field: '', operator: 'equals', value: '' });
    },

    addGroup(parent) {
        const arr = parent.all !== undefined ? parent.all : parent.any;
        arr.push({ any: [] });
    },

    removeItem(parent, index) {
        const arr = parent.all !== undefined ? parent.all : parent.any;
        arr.splice(index, 1);
    },

    serialize() {
        return JSON.stringify(this.root);
    }
}" x-init="$nextTick(() => $refs.conditionsInput.value = serialize()); $watch('root', () => $refs.conditionsInput.value = serialize())" class="conditions-builder">

    <input type="hidden" name="conditions_json" x-ref="conditionsInput" :value="serialize()">

    <div class="card border-0 bg-body-secondary p-3 mb-2">
        <div class="d-flex align-items-center gap-2 mb-3">
            <span class="fw-semibold">Match</span>
            <select class="form-select form-select-sm w-auto"
                    @change="toggleType(root)"
                    :value="getType(root)">
                <option value="all">ALL</option>
                <option value="any">ANY</option>
            </select>
            <span class="fw-semibold">of the following:</span>
        </div>

        <template x-for="(item, index) in getChildren(root)" :key="index">
            <div class="mb-2">
                <template x-if="!isGroup(item)">
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <select class="form-select form-select-sm" x-model="item.field" style="max-width: 180px;">
                            <option value="">-- field --</option>
                            @foreach($fields as $field)
                                <option value="{{ $field['key'] }}">{{ $field['label'] }}</option>
                            @endforeach
                        </select>
                        <select class="form-select form-select-sm" x-model="item.operator" style="max-width: 150px;">
                            @foreach($operators as $op)
                                <option value="{{ $op }}">{{ str_replace('_', ' ', $op) }}</option>
                            @endforeach
                        </select>
                        <template x-if="!['is_empty','is_not_empty','changed'].includes(item.operator)">
                            <input type="text" class="form-control form-control-sm" x-model="item.value" placeholder="value" style="max-width: 180px;">
                        </template>
                        <button type="button" class="btn btn-sm btn-outline-danger" @click="removeItem(root, index)">&times;</button>
                    </div>
                </template>

                <template x-if="isGroup(item)">
                    <div class="card border p-2">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="small fw-semibold">Match</span>
                            <select class="form-select form-select-sm w-auto"
                                    @change="toggleType(item)"
                                    :value="getType(item)">
                                <option value="all">ALL</option>
                                <option value="any">ANY</option>
                            </select>
                            <span class="small fw-semibold">of:</span>
                            <button type="button" class="btn btn-sm btn-outline-danger ms-auto" @click="removeItem(root, index)">&times;</button>
                        </div>

                        <template x-for="(leaf, leafIdx) in getChildren(item)" :key="leafIdx">
                            <div class="d-flex gap-2 align-items-center flex-wrap mb-1">
                                <select class="form-select form-select-sm" x-model="leaf.field" style="max-width: 180px;">
                                    <option value="">-- field --</option>
                                    @foreach($fields as $field)
                                        <option value="{{ $field['key'] }}">{{ $field['label'] }}</option>
                                    @endforeach
                                </select>
                                <select class="form-select form-select-sm" x-model="leaf.operator" style="max-width: 150px;">
                                    @foreach($operators as $op)
                                        <option value="{{ $op }}">{{ str_replace('_', ' ', $op) }}</option>
                                    @endforeach
                                </select>
                                <template x-if="!['is_empty','is_not_empty','changed'].includes(leaf.operator)">
                                    <input type="text" class="form-control form-control-sm" x-model="leaf.value" placeholder="value" style="max-width: 180px;">
                                </template>
                                <button type="button" class="btn btn-sm btn-outline-danger" @click="removeItem(item, leafIdx)">&times;</button>
                            </div>
                        </template>

                        <button type="button" class="btn btn-sm btn-outline-secondary mt-1" @click="addLeaf(item)">+ Condition</button>
                    </div>
                </template>
            </div>
        </template>

        <div class="d-flex gap-2 mt-2">
            <button type="button" class="btn btn-sm btn-outline-primary" @click="addLeaf(root)">+ Condition</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="addGroup(root)">+ Group</button>
        </div>
    </div>
</div>
