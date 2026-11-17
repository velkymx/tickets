import { withCsrf } from '../lib/csrf.js';

export async function pingWorker(postPing, getStatus, onUpdate, options = {}) {
    const interval = options.interval ?? 1000;
    const attempts = options.attempts ?? 10;
    const signal = options.signal;

    let token;

    try {
        token = await postPing();
    } catch (error) {
        onUpdate({ state: 'error', message: error.message });
        return;
    }

    if (!token) {
        onUpdate({ state: 'error', message: 'No ping token returned.' });
        return;
    }

    for (let i = 0; i < attempts; i += 1) {
        if (signal?.aborted) {
            return;
        }

        await new Promise((resolve) => { setTimeout(resolve, interval); });

        if (signal?.aborted) {
            return;
        }

        let status;

        try {
            status = await getStatus(token);
        } catch {
            continue;
        }

        if (status && status.status === 'answered') {
            onUpdate({ state: 'answered', duration: status.duration, connection: status.connection, queue: status.queue });
            return;
        }
    }

    onUpdate({ state: 'unanswered' });
}

export function initHealthPing(root = document) {
    const box = root.querySelector('[data-health-ping]');

    if (!box) {
        return;
    }

    const button = box.querySelector('[data-ping-start]');
    const result = box.querySelector('[data-ping-result]');

    if (!button || !result) {
        return;
    }

    const csrfHeaders = withCsrf({ 'Content-Type': 'application/json' });
    const statusUrl = box.dataset.statusUrl ?? '';
    let currentRun = null;

    button.addEventListener('click', async () => {
        currentRun?.abort();
        const controller = new AbortController();
        currentRun = controller;

        if (!statusUrl) {
            result.textContent = 'Status URL is not configured.';
            return;
        }

        button.disabled = true;
        result.textContent = 'Pinging worker…';

        await pingWorker(
            async () => {
                const response = await fetch(box.dataset.pingUrl, {
                    method: 'POST',
                    headers: csrfHeaders,
                    signal: controller.signal,
                });
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error ?? 'Ping failed.');
                }

                return data.token;
            },
            async (token) => {
                const response = await fetch(statusUrl.replace('TOKEN', token), {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });
                return response.json();
            },
            (update) => {
                if (update.state === 'answered') {
                    result.textContent = `Worker answered in ${update.duration} s (${update.connection}, queue ${update.queue}).`;
                } else if (update.state === 'unanswered') {
                    result.textContent = 'No worker picked up the ping in 10 s. Run `php artisan queue:work`.';
                } else {
                    result.textContent = `Ping failed: ${update.message}`;
                }
                button.disabled = false;
            },
            { signal: controller.signal },
        );
    });
}

document.addEventListener('DOMContentLoaded', () => initHealthPing());
