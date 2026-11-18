import { beforeEach, describe, expect, it, vi } from 'vitest';
import initBurndownChart from './BurndownChart.js';

const { render, register } = vi.hoisted(() => ({ render: vi.fn(), register: vi.fn() }));

vi.mock('chart.js', () => ({
    Chart: class {
        static register(...args) {
            register(...args);
        }

        constructor(...args) {
            render(...args);
        }
    },
    registerables: [],
}));

beforeEach(() => {
    render.mockClear();
    document.body.innerHTML = '';
});

describe('burndown chart', () => {
    it('ignores pages without a chart', async () => {
        await initBurndownChart(document);

        expect(render).not.toHaveBeenCalled();
    });

    it('ignores a chart without data', async () => {
        document.body.innerHTML = '<canvas id="burndownChart"></canvas>';

        await initBurndownChart(document);

        expect(render).not.toHaveBeenCalled();
    });

    it('renders the chart from the data attribute', async () => {
        const data = { labels: ['Mon', 'Tue'], ideal: [5, 0], actual: [5, 3] };
        document.body.innerHTML =
            `<canvas id="burndownChart" data-burndown='${JSON.stringify(data)}'></canvas>`;

        await initBurndownChart(document);

        expect(render).toHaveBeenCalledTimes(1);
        const [context, config] = render.mock.calls[0];
        expect(config.type).toBe('line');
        expect(config.data.labels).toEqual(['Mon', 'Tue']);
        expect(config.data.datasets).toHaveLength(2);
    });
});
