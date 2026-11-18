import { beforeEach, describe, expect, it, vi } from 'vitest';
import { initHealthPing, pingWorker } from './health-ping.js';

describe('pingWorker', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    it('stops polling once the worker answers', async () => {
        const postPing = vi.fn().mockResolvedValue('tok');
        const getStatus = vi.fn()
            .mockResolvedValueOnce({ status: 'pending' })
            .mockResolvedValue({ status: 'answered' });
        const onUpdate = vi.fn();

        const run = pingWorker(postPing, getStatus, onUpdate, { interval: 1000, attempts: 10 });
        await vi.advanceTimersByTimeAsync(5000);
        await run;

        expect(postPing).toHaveBeenCalledTimes(1);
        expect(onUpdate).toHaveBeenCalledWith({ state: 'answered' });
    });

    it('gives up after ten polls without an answer', async () => {
        const postPing = vi.fn().mockResolvedValue('tok');
        const getStatus = vi.fn().mockResolvedValue({ status: 'pending' });
        const onUpdate = vi.fn();

        const run = pingWorker(postPing, getStatus, onUpdate, { interval: 1000, attempts: 10 });
        await vi.advanceTimersByTimeAsync(11000);
        await run;

        expect(getStatus).toHaveBeenCalledTimes(10);
        expect(onUpdate).toHaveBeenCalledWith({ state: 'unanswered' });
    });

    it('reports a failed ping post', async () => {
        const postPing = vi.fn().mockRejectedValue(new Error('backend down'));
        const onUpdate = vi.fn();

        await pingWorker(postPing, vi.fn(), onUpdate, { interval: 1000, attempts: 10 });

        expect(onUpdate).toHaveBeenCalledWith({ state: 'error', message: 'backend down' });
    });

    it('stops polling once aborted', async () => {
        const controller = new AbortController();
        const postPing = vi.fn().mockResolvedValue('tok');
        const getStatus = vi.fn().mockResolvedValue({ status: 'pending' });
        const onUpdate = vi.fn();

        const run = pingWorker(postPing, getStatus, onUpdate, { interval: 1000, attempts: 10, signal: controller.signal });
        await vi.advanceTimersByTimeAsync(1500);
        controller.abort();
        await vi.advanceTimersByTimeAsync(10000);
        await run;

        expect(getStatus).toHaveBeenCalledTimes(1);
        expect(onUpdate).not.toHaveBeenCalled();
    });
});

describe('initHealthPing', () => {
    it('does not ping until the button is clicked', () => {
        document.body.innerHTML = `
            <div data-health-ping data-ping-url="/ping" data-status-url="/ping/TOKEN">
                <button data-ping-start>Ping worker</button>
                <span data-ping-result></span>
            </div>`;
        globalThis.fetch = vi.fn(() => new Promise(() => {}));

        initHealthPing(document);

        expect(globalThis.fetch).not.toHaveBeenCalled();
        expect(document.querySelector('[data-ping-start]').disabled).toBe(false);
    });

    it('pings once the button is clicked and passes an abort signal', async () => {
        document.body.innerHTML = `
            <div data-health-ping data-ping-url="/ping" data-status-url="/ping/TOKEN">
                <button data-ping-start>Ping worker</button>
                <span data-ping-result></span>
            </div>`;
        globalThis.fetch = vi.fn(() => new Promise(() => {}));

        initHealthPing(document);
        document.querySelector('[data-ping-start]').click();

        await vi.waitFor(() => expect(globalThis.fetch).toHaveBeenCalledTimes(1));
        expect(globalThis.fetch).toHaveBeenCalledWith('/ping', expect.objectContaining({
            method: 'POST',
            signal: expect.any(AbortSignal),
        }));
        expect(document.querySelector('[data-ping-start]').disabled).toBe(true);
    });

    it('reports a missing status url without fetching', () => {
        document.body.innerHTML = `
            <div data-health-ping data-ping-url="/ping">
                <button data-ping-start>Ping worker</button>
                <span data-ping-result></span>
            </div>`;
        globalThis.fetch = vi.fn();

        initHealthPing(document);
        document.querySelector('[data-ping-start]').click();

        expect(globalThis.fetch).not.toHaveBeenCalled();
        expect(document.querySelector('[data-ping-result]').textContent).toBe('Status URL is not configured.');
    });

    it('does nothing when the page has no ping button', () => {
        document.body.innerHTML = `
            <div data-health-ping data-ping-url="/ping" data-status-url="/ping/TOKEN">
                <p>The sync driver runs jobs inside the request.</p>
            </div>`;
        globalThis.fetch = vi.fn();

        initHealthPing(document);

        expect(globalThis.fetch).not.toHaveBeenCalled();
    });
});
