import Sortable from 'sortablejs';

export function initKanbanBoard(root = document) {
    const ticketColumns = root.querySelectorAll('.ticket-column');

    if (!ticketColumns.length) {
        return;
    }

    const alertDiv = root.querySelector('#update-alert') ?? document.querySelector('#update-alert');
    const alertMessage = root.querySelector('#update-message') ?? document.querySelector('#update-message');
    const closeAlertBtn = root.querySelector('#close-alert-btn') ?? document.querySelector('#close-alert-btn');

    if (!alertDiv || !alertMessage || !closeAlertBtn) {
        return;
    }

    let suppressClick = false;

    const showAlert = (type, message) => {
        alertMessage.textContent = message;
        alertDiv.classList.remove('d-none', 'alert-success', 'alert-danger');
        alertDiv.classList.add(`alert-${type}`, 'show');
    };

    const hideAlert = () => {
        alertDiv.classList.remove('show');
        alertDiv.classList.add('d-none');
    };

    const updateTicketStatus = (ticketId, newStatusId, onSuccess, onFailure) => {
        fetch(`/tickets/api/${ticketId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                Accept: 'application/json',
            },
            body: `status=${encodeURIComponent(newStatusId)}`,
        })
            .then((response) => {
                if (!response.ok) {
                    return response.json().catch(() => ({})).then((data) => {
                        throw new Error(data.message || data.error || `HTTP ${response.status}`);
                    });
                }
                return response.json();
            })
            .then((data) => {
                showAlert('success', `Ticket #${ticketId} updated.`);
                if (onSuccess) onSuccess(data);
            })
            .catch((error) => {
                showAlert('danger', `Error updating ticket ${ticketId}: ${error.message}. Change reverted.`);
                if (onFailure) onFailure(error);
            });
    };

    const checkEmptyColumn = (listElement) => {
        const items = listElement.querySelectorAll('li[data-ticket-id]');
        const placeholder = listElement.querySelector('[data-empty-placeholder]');

        if (!items.length && !placeholder) {
            const fresh = document.createElement('li');
            fresh.className = 'list-group-item list-group-item-light text-center fst-italic py-4';
            fresh.setAttribute('data-empty-placeholder', '');
            fresh.textContent = 'Drop tickets here';
            listElement.appendChild(fresh);
        }

        if (items.length && placeholder) {
            placeholder.remove();
        }
    };

    // Suppress link navigation when a drag just ended (click vs drag fight).
    document.addEventListener('click', (event) => {
        if (suppressClick && event.target.closest('.ticket-column a')) {
            event.preventDefault();
            event.stopPropagation();
        }
    }, true);

    ticketColumns.forEach((column) => {
        new Sortable(column, {
            group: 'tickets-board',
            animation: 150,
            draggable: 'li[data-ticket-id]',
            filter: '[data-empty-placeholder]',
            preventOnFilter: true,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            delay: 80,
            delayOnTouchOnly: true,
            scrollSensitivity: 30,
            scrollSpeed: 4,
            bubbleScroll: true,
            emptyInsertThreshold: 20,
            fallbackTolerance: 3,
            swapThreshold: 0.65,
            invertSwap: true,

            onStart: () => {
                suppressClick = true;
            },

            onEnd: (evt) => {
                setTimeout(() => { suppressClick = false; }, 250);

                const ticketItem = evt.item;
                const ticketId = ticketItem.getAttribute('data-ticket-id');

                // Placeholder is filtered out; ignore anything without an id.
                if (!ticketId) {
                    return;
                }

                const oldList = evt.from;
                const newList = evt.to;
                const oldIndex = evt.oldIndex;

                // Same-column reorder: nothing to persist.
                if (oldList === newList) {
                    return;
                }

                const revertMove = () => {
                    const ref = oldList.children[oldIndex] || null;
                    oldList.insertBefore(ticketItem, ref);
                    checkEmptyColumn(oldList);
                    checkEmptyColumn(newList);
                };

                updateTicketStatus(ticketId, newList.getAttribute('data-status-id'), null, revertMove);

                // Handle the empty state visually
                checkEmptyColumn(oldList);
                checkEmptyColumn(newList);
            },
        });
    });

    closeAlertBtn.addEventListener('click', () => {
        hideAlert();
    });

    ticketColumns.forEach(checkEmptyColumn);
}

document.addEventListener('DOMContentLoaded', () => initKanbanBoard());
