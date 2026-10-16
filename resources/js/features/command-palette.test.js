import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import commandPalette from './command-palette.js';

function palette() {
    const pal = commandPalette();
    pal.go = vi.fn();
    return pal;
}

beforeEach(() => {
    vi.useFakeTimers();
    vi.stubGlobal('fetch', vi.fn());
    localStorage.clear();
});

afterEach(() => {
    vi.useRealTimers();
    vi.unstubAllGlobals();
});

describe('palette input', () => {
    it('ignores queries under two characters', async () => {
        const pal = palette();
        pal.query = 'l';

        pal.onInput();
        await vi.runAllTimersAsync();

        expect(fetch).not.toHaveBeenCalled();
        expect(pal.results).toEqual([]);
    });

    it('debounces rapid keystrokes into one search', async () => {
        vi.mocked(fetch).mockResolvedValue({ ok: true, json: async () => [] });
        const pal = palette();
        pal.query = 'log';

        pal.onInput();
        pal.query = 'logi';
        pal.onInput();
        await vi.runAllTimersAsync();

        expect(fetch).toHaveBeenCalledTimes(1);
        expect(fetch).toHaveBeenCalledWith(
            '/palette/search?q=logi',
            expect.objectContaining({ headers: { Accept: 'application/json' } })
        );
    });
});

describe('palette sequence guard', () => {
    it('discards a stale response that resolves last', async () => {
        let firstResolve;
        let secondResolve;
        vi.mocked(fetch)
            .mockImplementationOnce(() => new Promise((r) => { firstResolve = r; }))
            .mockImplementationOnce(() => new Promise((r) => { secondResolve = r; }));
        const pal = palette();

        pal.query = 'alpha';
        const first = pal.search();
        pal.query = 'alphabet';
        const second = pal.search();

        secondResolve({ ok: true, json: async () => [{ id: 2, subject: 'alphabet' }] });
        await second;
        firstResolve({ ok: true, json: async () => [{ id: 1, subject: 'alpha' }] });
        await first;

        expect(pal.results).toEqual([{ id: 2, subject: 'alphabet' }]);
        expect(pal.loading).toBe(false);
    });

    it('clears results on network failure', async () => {
        vi.mocked(fetch).mockRejectedValue(new Error('down'));
        const pal = palette();
        pal.query = 'login';

        await pal.search();

        expect(pal.results).toEqual([]);
        expect(pal.loading).toBe(false);
    });
});

describe('palette keyboard state', () => {
    it('clamps the cursor when the list shrinks', () => {
        const pal = palette();
        pal.query = 'home';
        pal.results = [{ id: 1, subject: 'one', url: '/tickets/1' }];
        pal.active = 5;

        pal.chooseCurrent();

        expect(pal.go).toHaveBeenCalledWith(
            expect.objectContaining({ id: 1 })
        );
    });

    it('moves within bounds and wraps', () => {
        const pal = palette();
        pal.query = 'home';
        pal.results = [{ id: 1, subject: 'one', url: '/tickets/1' }];

        expect(pal.items()).toHaveLength(2);
        pal.move(1);
        expect(pal.active).toBe(1);
        pal.move(1);
        expect(pal.active).toBe(0);
    });
});
