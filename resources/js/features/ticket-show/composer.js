function insertAtCursor(textarea, text) {
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    textarea.value = textarea.value.substring(0, start) + text + textarea.value.substring(end);
    textarea.setSelectionRange(start + text.length, start + text.length);
}

function wireSubmitShortcut(textarea) {
    textarea.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
            e.preventDefault();
            this.closest('form').submit();
        }
    });
}

function wireSmartPaste(textarea) {
    textarea.addEventListener('paste', function (e) {
        const text = (e.clipboardData || window.clipboardData).getData('text');
        if (!text) return;

        const stackTracePattern = /^\s*(at |Traceback|Exception|Error:|Fatal|#\d+ |\.php:\d+|\.js:\d+|\.py:\d+)/m;
        if (stackTracePattern.test(text) && text.split('\n').length > 3) {
            e.preventDefault();
            insertAtCursor(this, '```\n' + text.trim() + '\n```');
            return;
        }

        try {
            const parsed = JSON.parse(text);
            if (typeof parsed === 'object') {
                e.preventDefault();
                insertAtCursor(this, '```json\n' + JSON.stringify(parsed, null, 2) + '\n```');
                return;
            }
        } catch (_) {}

        const urlPattern = /^https?:\/\/\S+$/;
        if (urlPattern.test(text.trim())) {
            e.preventDefault();
            insertAtCursor(this, '[' + text.trim() + '](' + text.trim() + ')');
        }
    });
}

function wireSignalNudge(textarea) {
    textarea.addEventListener('input', function () {
        const nudge = document.getElementById('signal-nudge');
        if (!nudge) return;
        const blockerKeywords = /\b(blocked|waiting on|depends on|can't proceed|cannot proceed)\b/i;
        if (blockerKeywords.test(this.value)) {
            nudge.textContent = 'Tip: Use /blocker to make this visible in Ticket Pulse';
            nudge.classList.remove('d-none');
        } else {
            nudge.classList.add('d-none');
        }
    });
}

function wireToolbar() {
    document.querySelectorAll('.markdown-composer [data-md]').forEach(btn => {
        btn.addEventListener('click', function () {
            const textarea = document.getElementById('note-textarea');
            if (!textarea) return;
            const md = this.dataset.md;
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const selected = textarea.value.substring(start, end);
            textarea.value = textarea.value.substring(0, start) + md + selected + md + textarea.value.substring(end);
            textarea.focus();
            textarea.setSelectionRange(start + md.length, end + md.length);
        });
    });
}

function wireMentions(textarea) {
    const composer = textarea.closest('.markdown-composer');
    if (!composer) return;

    const dropdown = composer.querySelector('.mention-autocomplete');
    const users = JSON.parse(composer.dataset.users || '[]');
    let mentionStart = -1;
    let activeIndex = -1;

    function setActive(index) {
        const items = dropdown.querySelectorAll('.dropdown-item');
        items.forEach(i => i.classList.remove('active'));
        if (index >= 0 && index < items.length) {
            activeIndex = index;
            items[index].classList.add('active');
            items[index].scrollIntoView({ block: 'nearest' });
        }
    }

    function hide() {
        dropdown.classList.add('d-none');
        dropdown.classList.remove('show');
        mentionStart = -1;
        activeIndex = -1;
    }

    function selectUser(u) {
        const token = u.title ? `@[${u.name} (${u.title})]` : `@[${u.name}]`;
        const before = textarea.value.substring(0, mentionStart);
        const after = textarea.value.substring(textarea.selectionStart);
        textarea.value = before + token + ' ' + after;
        const cursorPos = before.length + token.length + 1;
        textarea.setSelectionRange(cursorPos, cursorPos);
        hide();
        textarea.focus();
    }

    function buildDropdown(filter) {
        const lower = filter.toLowerCase();
        const filtered = lower === ''
            ? users
            : users.filter(u => u.name.toLowerCase().includes(lower));

        dropdown.innerHTML = '';
        activeIndex = -1;

        if (filtered.length === 0) {
            hide();
            return;
        }

        filtered.forEach((u) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'dropdown-item';
            btn.dataset.userId = u.id;
            btn.dataset.userName = u.name;
            btn.dataset.userTitle = u.title || '';
            btn.textContent = u.title ? `${u.name} (${u.title})` : u.name;
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
                selectUser(u);
            });
            dropdown.appendChild(btn);
        });

        dropdown.classList.remove('d-none');
        dropdown.classList.add('show');
        setActive(0);
    }

    textarea.addEventListener('input', function () {
        const pos = this.selectionStart;
        const text = this.value.substring(0, pos);

        const lastAt = text.lastIndexOf('@');
        if (lastAt === -1 || (lastAt > 0 && /\w/.test(text[lastAt - 1]))) {
            hide();
            return;
        }

        const afterAt = text.substring(lastAt);
        if (afterAt.includes(']')) {
            hide();
            return;
        }

        mentionStart = lastAt;
        buildDropdown(text.substring(lastAt + 1));
    });

    textarea.addEventListener('keydown', function (e) {
        if (mentionStart === -1) return;

        const items = dropdown.querySelectorAll('.dropdown-item');
        if (items.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActive(Math.min(activeIndex + 1, items.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActive(Math.max(activeIndex - 1, 0));
        } else if (e.key === 'Enter' && activeIndex >= 0) {
            e.preventDefault();
            const item = items[activeIndex];
            selectUser({
                id: item.dataset.userId,
                name: item.dataset.userName,
                title: item.dataset.userTitle,
            });
        } else if (e.key === 'Escape') {
            e.preventDefault();
            hide();
        }
    });

    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target) && e.target !== textarea) {
            hide();
        }
    });
}

export default function initComposer() {
    wireToolbar();

    const textarea = document.getElementById('note-textarea');
    if (!textarea) return;

    wireSubmitShortcut(textarea);
    wireSmartPaste(textarea);
    wireSignalNudge(textarea);
    wireMentions(textarea);
}
