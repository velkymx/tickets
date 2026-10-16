const RECENT_KEY = 'palette-recents';
const RECENT_LIMIT = 5;

function fuzzyScore(text, query) {
    const hay = text.toLowerCase();
    const needle = query.toLowerCase();

    if (hay.includes(needle)) {
        return needle.length * 2 - hay.indexOf(needle) / 100;
    }

    let score = 0;
    let pos = 0;

    for (const char of needle) {
        const found = hay.indexOf(char, pos);
        if (found === -1) {
            return -1;
        }
        score += 1;
        pos = found + 1;
    }

    return score;
}

function loadRecents() {
    try {
        return JSON.parse(localStorage.getItem(RECENT_KEY)) ?? [];
    } catch {
        return [];
    }
}

export default function commandPalette() {
    return {
        open: false,
        query: '',
        results: [],
        active: 0,
        loading: false,
        recents: loadRecents(),
        timer: null,
        seq: 0,

        destinations: [
            { label: 'Home', hint: 'Dashboard', url: '/home' },
            { label: 'All Tickets', hint: 'List', url: '/tickets' },
            { label: 'Kanban Board', hint: 'Board', url: '/tickets/board' },
            { label: 'Milestones', hint: 'List', url: '/milestone' },
            { label: 'Projects', hint: 'List', url: '/projects' },
            { label: 'Knowledge Base', hint: 'Docs', url: '/kb' },
            { label: 'Activity', hint: 'Notifications', url: '/activity' },
        ],

        actions: [
            { label: 'Create Ticket', hint: 'Action', url: '/ticket/create' },
            { label: 'Create Article', hint: 'Action', url: '/kb/create' },
        ],

        init() {
            document.addEventListener('keydown', (e) => {
                const inField = /^(input|textarea|select)$/i.test(document.activeElement?.tagName ?? '');

                if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                    e.preventDefault();
                    this.toggle();
                } else if (e.key === 'Escape' && this.open) {
                    this.close();
                } else if (inField) {
                    return;
                }
            });
        },

        toggle() {
            this.open ? this.close() : this.openPalette();
        },

        openPalette() {
            this.open = true;
            this.query = '';
            this.results = [];
            this.active = 0;
            this.$nextTick(() => this.$refs.input?.focus());
        },

        close() {
            this.open = false;
            clearTimeout(this.timer);
        },

        staticMatches() {
            if (! this.query) {
                return [];
            }

            return [...this.destinations, ...this.actions]
                .map((item) => ({ ...item, score: fuzzyScore(item.label, this.query) }))
                .filter((item) => item.score > 0)
                .sort((a, b) => b.score - a.score)
                .slice(0, 4);
        },

        items() {
            if (! this.query) {
                return this.recents.map((item) => ({ ...item, group: 'Recent' }));
            }

            const statics = this.staticMatches().map((item) => ({ ...item, group: 'Go to' }));
            const tickets = this.results.map((item) => ({ ...item, group: 'Tickets' }));

            return [...statics, ...tickets];
        },

        onInput() {
            clearTimeout(this.timer);
            this.active = 0;

            if (this.query.trim().length < 2) {
                this.seq++;
                this.results = [];

                return;
            }

            this.timer = setTimeout(() => this.search(), 150);
        },

        async search() {
            const seq = ++this.seq;
            this.loading = true;

            try {
                const response = await fetch(`/palette/search?q=${encodeURIComponent(this.query.trim())}`, {
                    headers: { Accept: 'application/json' },
                });
                if (seq !== this.seq) {
                    return;
                }
                this.results = response.ok ? await response.json() : [];
            } catch {
                if (seq === this.seq) {
                    this.results = [];
                }
            } finally {
                if (seq === this.seq) {
                    this.loading = false;
                    this.active = 0;
                }
            }
        },

        move(delta) {
            const count = this.items().length;
            if (! count) {
                return;
            }
            this.active = (this.active + delta + count) % count;
        },

        chooseCurrent() {
            const list = this.items();
            const item = list[Math.min(this.active, Math.max(list.length - 1, 0))];
            if (item) {
                this.go(item);
            }
        },

        go(item) {
            if (item.id) {
                const recents = [{ id: item.id, label: `#${item.id} ${item.subject}`, url: item.url },
                    ...loadRecents().filter((recent) => recent.id !== item.id)].slice(0, RECENT_LIMIT);
                try {
                    localStorage.setItem(RECENT_KEY, JSON.stringify(recents));
                } catch {
                    // Storage unavailable (private mode); palette still navigates.
                }
            }
            window.location.href = item.url;
        },
    };
}
