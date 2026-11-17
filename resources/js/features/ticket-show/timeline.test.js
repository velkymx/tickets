import { beforeEach, describe, expect, it, vi } from 'vitest';
import initTimeline from './timeline.js';

beforeEach(() => {
    vi.stubGlobal('fetch', vi.fn());
});

describe('timeline guards', () => {
    it('ignores pages without timeline markup', () => {
        document.body.innerHTML = '<p>No timeline here</p>';

        expect(() => initTimeline()).not.toThrow();
    });

    it('handles each reaction submit only once', async () => {
        document.body.innerHTML = `
            <form class="reaction-toggle-form" action="/notes/1/react" method="POST">
                <button type="submit">x</button>
            </form>`;
        vi.mocked(fetch).mockResolvedValue({ ok: true, json: async () => ({}) });

        initTimeline();
        initTimeline();

        document.querySelector('form').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));

        await vi.waitFor(() => expect(fetch).toHaveBeenCalledTimes(1));
    });
});
