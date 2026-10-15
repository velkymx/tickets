function escapeHtml(s) {
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function gravatarHash(email) {
    if (!email || typeof md5 !== 'function') {
        return '';
    }
    return md5(email.toLowerCase().trim());
}

function updatePresenceUI(viewers) {
    const container = document.getElementById('presence-viewers');
    if (!container || !viewers) return;

    const maxShow = 3;
    let html = '<div class="d-flex" style="margin-left: -8px;">';

    viewers.slice(0, maxShow).forEach((v, i) => {
        const hash = gravatarHash(v.email);
        const composingDot = v.composing
            ? '<span class="position-absolute bottom-0 end-0 badge bg-success rounded-circle" style="width:8px;height:8px;padding:0;">…</span>'
            : '';
        html += `<div class="position-relative" style="margin-left:-8px;z-index:${maxShow - i};" title="${escapeHtml(v.name)}">
            <img src="https://www.gravatar.com/avatar/${hash}?s=28&d=mp" class="rounded-circle border border-2 border-white" width="28" height="28">
            ${composingDot}
        </div>`;
    });

    if (viewers.length > maxShow) {
        html += `<div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center small" style="width:28px;height:28px;margin-left:-8px;">+${viewers.length - maxShow}</div>`;
    }

    html += '</div>';
    container.innerHTML = html;
}

export default function initPresence() {
    const ticketId = document.querySelector('.presence-indicator')?.dataset.ticketId;
    if (!ticketId) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    function sendHeartbeat() {
        const composing = document.activeElement?.id === 'note-textarea';
        fetch(`/tickets/${ticketId}/presence`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ composing }),
        })
            .then(r => r.json())
            .then(data => updatePresenceUI(data.viewers))
            .catch(() => {});
    }

    sendHeartbeat();
    const heartbeatInterval = setInterval(sendHeartbeat, 15000);

    const activityInterval = setInterval(() => {
        fetch(`/tickets/${ticketId}/presence`, {
            headers: { 'Accept': 'application/json' },
        })
            .then(r => r.json())
            .then(() => {})
            .catch(() => {});
    }, 30000);

    window.addEventListener('beforeunload', () => {
        clearInterval(heartbeatInterval);
        clearInterval(activityInterval);
    });
}
