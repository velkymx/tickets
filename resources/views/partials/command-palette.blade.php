@auth
<div x-data="commandPalette" @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="chooseCurrent()">
    <div x-show="open" x-cloak class="position-fixed top-0 start-0 w-100 h-100" style="z-index: 1055; background: rgba(0, 0, 0, 0.45);" @click.self="close()">
        <div class="mx-auto mt-5 bg-body rounded shadow-lg overflow-hidden" style="max-width: 560px;">
            <div class="d-flex align-items-center gap-2 border-bottom px-3 py-2">
                <i class="fas fa-search text-muted"></i>
                <input x-ref="input" x-model="query" @input="onInput()" type="text" class="form-control border-0 shadow-none" placeholder="Jump to tickets, pages, actions..." autocomplete="off">
                <kbd class="text-muted small">esc</kbd>
            </div>
            <div class="overflow-auto" style="max-height: 320px;">
                <template x-if="loading">
                    <div class="px-3 py-2 text-muted small">Searching...</div>
                </template>
                <template x-for="(item, index) in items()" :key="item.url + item.label">
                    <a :href="item.url" @click.prevent="go(item)" class="d-flex justify-content-between align-items-center px-3 py-2 text-decoration-none text-body" :class="{ 'bg-body-secondary': index === active }" @mousemove="active = index">
                        <span x-text="item.id ? '#' + item.id + ' ' + item.subject : item.label"></span>
                        <span class="badge bg-secondary" x-text="item.group"></span>
                    </a>
                </template>
                <template x-if="!loading && items().length === 0">
                    <div class="px-3 py-3 text-muted small" x-text="query ? 'No matches. Keep typing to search tickets.' : 'Type to search. Recent tickets appear here.'"></div>
                </template>
            </div>
        </div>
    </div>
</div>
@endauth
