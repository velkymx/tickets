import { describe, expect, it } from 'vitest';
import { actionBuilder, conditionBuilder } from './automation-builders.js';

describe('conditionBuilder', () => {
    it('serializes added rows', () => {
        const builder = conditionBuilder();
        builder.addLeaf(builder.root);
        builder.root.all[0].field = 'status';
        builder.root.all[0].operator = 'equals';
        builder.root.all[0].value = 'open';

        expect(builder.serialize()).toBe(
            JSON.stringify({ all: [{ field: 'status', operator: 'equals', value: 'open' }] }),
        );
    });

    it('toggles a group between all and any', () => {
        const builder = conditionBuilder();
        builder.addGroup(builder.root);
        builder.toggleType(builder.root.all[0]);

        expect(builder.serialize()).toBe(JSON.stringify({ all: [{ all: [] }] }));
    });
});

describe('actionBuilder', () => {
    it('serializes an added webhook action', () => {
        const builder = actionBuilder();
        builder.addAction();
        builder.actions[0].config.url = 'https://hooks.example.com/test';

        const parsed = JSON.parse(builder.serialize());

        expect(parsed).toHaveLength(1);
        expect(parsed[0].type).toBe('call_webhook');
        expect(parsed[0].config.url).toBe('https://hooks.example.com/test');
    });

    it('removes actions by index', () => {
        const builder = actionBuilder();
        builder.addAction();
        builder.addAction();
        builder.removeAction(0);

        expect(JSON.parse(builder.serialize())).toHaveLength(1);
    });
});
