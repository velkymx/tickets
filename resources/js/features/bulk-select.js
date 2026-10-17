function initBulkSelect(container) {
    const boxes = Array.from(container.querySelectorAll('input[name^="tickets["]'));
    if (!boxes.length) return;

    // The ticket table renders each row twice (table pane plus card pane)
    // with one pane always display:none at the md breakpoint (768px).
    // Pane membership is explicit via data-pane, matched against the same
    // breakpoint the Blade classes use, so a future display:none ancestor
    // elsewhere (offcanvas, print) cannot silently empty the visible set.
    // Boxes outside any pane (other bulk-select consumers) always count.
    const isDesktopPane = () => window.matchMedia('(min-width: 768px)').matches;
    const visibleBoxes = () => boxes.filter(cb => {
        const pane = cb.closest('[data-pane]');
        if (!pane) return true;
        return (pane.dataset.pane === 'table') === isDesktopPane();
    });
    const selectAll = container.querySelector('#selectAll');
    const toggle = container.querySelector('#checkAll');

    const syncTwins = (source) => {
        boxes.forEach(other => {
            if (other !== source && other.name === source.name) {
                other.checked = source.checked;
            }
        });
    };

    const refreshSelectAll = () => {
        if (!selectAll) return;
        // Empty active pane means nothing visible to reflect; leave the
        // header checkbox untouched rather than deriving state from air.
        const visible = visibleBoxes();
        if (visible.length) {
            selectAll.checked = visible.every(c => c.checked);
        }
    };

    boxes.forEach(cb => {
        cb.addEventListener('change', () => {
            syncTwins(cb);
            refreshSelectAll();
        });
    });

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            boxes.forEach(cb => { cb.checked = selectAll.checked; });
        });
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            const candidates = visibleBoxes();
            const pool = candidates.length ? candidates : boxes;
            const shouldCheck = pool.some(cb => !cb.checked);
            boxes.forEach(cb => { cb.checked = shouldCheck; });
            if (selectAll) selectAll.checked = shouldCheck;
            this.textContent = shouldCheck ? 'Uncheck All' : 'Check All';
        });
    }
}

export default function initBulkSelectors(root = document) {
    root.querySelectorAll('[data-bulk-select]').forEach(initBulkSelect);
}

document.addEventListener('DOMContentLoaded', () => initBulkSelectors());
