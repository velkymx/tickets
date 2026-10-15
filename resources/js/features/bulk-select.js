function initBulkSelect(container) {
    const rows = container.querySelectorAll('input[name^="tickets["]');
    if (!rows.length) return;

    const selectAll = container.querySelector('#selectAll');
    const toggle = container.querySelector('#checkAll');

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            rows.forEach(cb => { cb.checked = selectAll.checked; });
        });

        rows.forEach(cb => {
            cb.addEventListener('change', () => {
                selectAll.checked = Array.from(rows).every(c => c.checked);
            });
        });
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            const shouldCheck = Array.from(rows).some(cb => !cb.checked);
            rows.forEach(cb => { cb.checked = shouldCheck; });
            if (selectAll) selectAll.checked = shouldCheck;
            this.textContent = shouldCheck ? 'Uncheck All' : 'Check All';
        });
    }
}

export default function initBulkSelectors(root = document) {
    root.querySelectorAll('[data-bulk-select]').forEach(initBulkSelect);
}

document.addEventListener('DOMContentLoaded', () => initBulkSelectors());
