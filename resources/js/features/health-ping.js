export async function pingWorker(postPing, getStatus, onUpdate, options = {}) {
    const interval = options.interval ?? 1000;
    const attempts = options.attempts ?? 10;

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
        await new Promise((resolve) => { setTimeout(resolve, interval); });

        let status;

        try {
            status = await getStatus(token);
        } catch {
            continue;
        }

        if (status && status.status === 'answered') {
            onUpdate({ state: 'answered', duration: status.duration });
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

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const statusUrl = box.dataset.statusUrl ?? '';

    button.addEventListener('click', async () => {
        button.disabled = true;
        result.textContent = 'Pinging worker…';

        await pingWorker(
            async () => {
                const response = await fetch(box.dataset.pingUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
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
                });
                return response.json();
            },
            (update) => {
                if (update.state === 'answered') {
                    result.textContent = `Worker answered in ${update.duration} s.`;
                } else if (update.state === 'unanswered') {
                    result.textContent = 'No worker picked up the ping in 10 s. Run `php artisan queue:work`.';
                } else {
                    result.textContent = `Ping failed: ${update.message}`;
                }
                button.disabled = false;
            },
        );
    });
}

document.addEventListener('DOMContentLoaded', () => initHealthPing());
