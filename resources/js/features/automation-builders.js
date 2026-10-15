export function conditionBuilder() {
    return {
        root: { all: [] },

        init() {
            const raw = this.$el.dataset.initial;

            if (raw) {
                try {
                    this.root = JSON.parse(raw);
                } catch (e) {
                    this.root = { all: [] };
                }
            }

            this.$nextTick(() => {
                this.$refs.conditionsInput.value = this.serialize();
            });

            this.$watch('root', () => {
                this.$refs.conditionsInput.value = this.serialize();
            });
        },

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
        },
    };
}

export function actionBuilder() {
    return {
        actions: [],

        init() {
            const raw = this.$el.dataset.initial;

            if (raw) {
                try {
                    this.actions = JSON.parse(raw);
                } catch (e) {
                    this.actions = [];
                }
            }

            this.$nextTick(() => {
                this.$refs.actionsInput.value = this.serialize();
            });

            this.$watch('actions', () => {
                this.$refs.actionsInput.value = this.serialize();
            });
        },

        addAction() {
            this.actions.push({
                type: 'call_webhook',
                config: {
                    url: '',
                    method: 'POST',
                    headers: {},
                    payload: {},
                },
            });
        },

        removeAction(index) {
            this.actions.splice(index, 1);
        },

        serialize() {
            return JSON.stringify(this.actions);
        },
    };
}
