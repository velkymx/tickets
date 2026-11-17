import { beforeEach, describe, expect, it, vi } from 'vitest';
import initPresence from './presence.js';

beforeEach(() => {
    vi.stubGlobal('fetch', vi.fn());
    document.body.innerHTML = `
        <meta name="csrf-token" content="test-token">
        <div class="presence-indicator" data-ticket-id="5"></div>
        <div id="presence-viewers"></div>`;
});

describe('presence avatars', () => {
    it('renders the server-provided avatar instead of an empty hash', async () => {
        vi.mocked(fetch).mockResolvedValue({
            json: async () => ({ viewers: [
                { user_id: 1, name: 'Ann', avatar_url: 'https://example.com/ann.png', last_seen: 1 },
            ] }),
        });

        initPresence();

        await vi.waitFor(() => expect(document.querySelector('#presence-viewers img')).not.toBeNull());

        expect(document.querySelector('#presence-viewers img').getAttribute('src'))
            .toBe('https://example.com/ann.png');
    });
});
