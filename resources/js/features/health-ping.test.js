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
            .mockResolvedValue({ status: 'answered', duration: 1.2 });
        const onUpdate = vi.fn();

        const run = pingWorker(postPing, getStatus, onUpdate, { interval: 1000, attempts: 10 });
        await vi.advanceTimersByTimeAsync(5000);
        await run;

        expect(postPing).toHaveBeenCalledTimes(1);
        expect(onUpdate).toHaveBeenCalledWith({ state: 'answered', duration: 1.2 });
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
});

describe('initHealthPing', () => {
    it('starts the ping on init when the button exists', () => {
        document.body.innerHTML = `
            <div data-health-ping data-ping-url="/ping" data-status-url="/ping/TOKEN">
                <button data-ping-start>Ping worker</button>
                <span data-ping-result></span>
            </div>`;
        globalThis.fetch = vi.fn(() => new Promise(() => {}));

        initHealthPing(document);

        expect(globalThis.fetch).toHaveBeenCalledWith('/ping', expect.objectContaining({ method: 'POST' }));
        expect(document.querySelector('[data-ping-start]').disabled).toBe(true);
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
