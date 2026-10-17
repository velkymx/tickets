import { beforeEach, describe, expect, it, vi } from 'vitest';
import { pingWorker } from './health-ping.js';

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
