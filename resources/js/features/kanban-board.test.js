import { beforeEach, describe, expect, it, vi } from 'vitest';
import { initKanbanBoard } from './kanban-board.js';

const seen = [];

vi.mock('sortablejs', () => ({
    default: vi.fn().mockImplementation(function (element, options) {
        seen.push({ element, options });
    }),
}));

function mount() {
    document.body.innerHTML = `
        <meta name="csrf-token" content="test-token">
        <div id="update-alert" class="alert alert-success alert-dismissible fade d-none" role="alert">
            <span id="update-message"></span>
            <button type="button" class="btn-close" aria-label="Close" id="close-alert-btn"></button>
        </div>
        <ol class="ticket-column" data-status-id="1">
            <li data-ticket-id="7"><a href="/tickets/7">Ticket seven</a></li>
        </ol>
        <ol class="ticket-column" data-status-id="2">
            <li data-empty-placeholder>Drop tickets here</li>
        </ol>`;
    initKanbanBoard(document);
    const columns = [...document.querySelectorAll('.ticket-column')];
    return {
        alert: document.querySelector('#update-alert'),
        message: document.querySelector('#update-message'),
        from: columns[0],
        to: columns[1],
    };
}

function drop(from, to, ticketId, oldIndex = 0) {
    const item = from.querySelector(`[data-ticket-id="${ticketId}"]`);
    to.appendChild(item);
    const wiring = seen.find((entry) => entry.element === to);
    wiring.options.onEnd({ item, from, to, oldIndex, newIndex: 0 });
}

beforeEach(() => {
    seen.length = 0;
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true, json: async () => ({}) }));
});

describe('kanban-board wiring', () => {
    it('ignores pages without a board', () => {
        document.body.innerHTML = '<p>No board here</p>';

        initKanbanBoard(document);

        expect(seen).toHaveLength(0);
    });

    it('makes every column sortable in one shared group', () => {
        const { from, to } = mount();

        expect(seen).toHaveLength(2);
        for (const entry of seen) {
            expect(entry.options.group).toBe('tickets-board');
            expect(entry.options.draggable).toBe('li[data-ticket-id]');
        }
        expect(seen.map((entry) => entry.element)).toEqual([from, to]);
    });
});

describe('kanban-board drop', () => {
    it('posts the new status and confirms on success', async () => {
        const { alert, from, message, to } = mount();
        vi.mocked(fetch).mockResolvedValue({ ok: true, json: async () => ({ status: 'success' }) });

        drop(from, to, '7');
        await vi.waitFor(() => expect(message.textContent).toBe('Ticket #7 updated.'));

        expect(fetch).toHaveBeenCalledTimes(1);
        expect(fetch).toHaveBeenCalledWith(
            '/tickets/api/7',
            expect.objectContaining({
                method: 'POST',
                body: `status=${encodeURIComponent('2')}`,
            })
        );
        expect(fetch.mock.calls[0][1].headers['X-CSRF-TOKEN']).toBe('test-token');
        expect(alert.classList.contains('alert-success')).toBe(true);
        expect(message.textContent).toBe('Ticket #7 updated.');
    });

    it('reverts the move and warns on failure', async () => {
        const { alert, from, message, to } = mount();
        vi.mocked(fetch).mockResolvedValue({
            ok: false,
            status: 422,
            json: async () => ({ message: 'Invalid.' }),
        });

        drop(from, to, '7');
        await vi.waitFor(() => expect(message.textContent).toContain('Change reverted.'));

        expect(fetch).toHaveBeenCalledTimes(1);

        expect(from.querySelector('[data-ticket-id="7"]')).not.toBeNull();
        expect(to.querySelector('[data-ticket-id="7"]')).toBeNull();
        expect(alert.classList.contains('alert-danger')).toBe(true);
        expect(message.textContent).toContain('Change reverted.');
    });

    it('posts nothing for same-column reorders', () => {
        const { from } = mount();
        const wiring = seen.find((entry) => entry.element === from);
        const item = from.querySelector('[data-ticket-id="7"]');

        wiring.options.onEnd({ item, from, to: from, oldIndex: 0, newIndex: 0 });

        expect(fetch).not.toHaveBeenCalled();
    });
});

describe('kanban-board guards', () => {
    it('posts without a CSRF token when the meta tag is missing', async () => {
        document.body.innerHTML = `
            <div id="update-alert" class="alert alert-success alert-dismissible fade d-none" role="alert">
                <span id="update-message"></span>
                <button type="button" class="btn-close" aria-label="Close" id="close-alert-btn"></button>
            </div>
            <ol class="ticket-column" data-status-id="1">
                <li data-ticket-id="7"><a href="/tickets/7">Ticket seven</a></li>
            </ol>
            <ol class="ticket-column" data-status-id="2">
                <li data-empty-placeholder>Drop tickets here</li>
            </ol>`;
        initKanbanBoard(document);
        const columns = [...document.querySelectorAll('.ticket-column')];

        drop(columns[0], columns[1], '7');
        await vi.waitFor(() => expect(fetch).toHaveBeenCalledTimes(1));

        expect(fetch.mock.calls[0][1].headers['X-CSRF-TOKEN']).toBe('');
    });

    it('ignores boards without alert nodes', () => {
        document.body.innerHTML = `
            <ol class="ticket-column" data-status-id="1">
                <li data-ticket-id="7">Ticket seven</li>
            </ol>`;

        expect(() => initKanbanBoard(document)).not.toThrow();
        expect(seen).toHaveLength(0);
    });
});

describe('kanban-board placeholders', () => {
    it('adds a prompt to the emptied column and clears the filled one', () => {
        const { from, to } = mount();

        drop(from, to, '7');

        expect(from.querySelector('[data-empty-placeholder]')).not.toBeNull();
        expect(to.querySelector('[data-empty-placeholder]')).toBeNull();
    });

    it('closes the alert on button click', () => {
        const { alert } = mount();
        alert.classList.remove('d-none');

        document.querySelector('#close-alert-btn').dispatchEvent(new Event('click', { bubbles: true }));

        expect(alert.classList.contains('d-none')).toBe(true);
    });
});
