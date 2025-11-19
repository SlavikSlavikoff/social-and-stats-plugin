document.addEventListener('DOMContentLoaded', () => {
    initTimelinePeriodSorters();
    initTimelineCardSorters();
});

function initTimelinePeriodSorters() {
    document.querySelectorAll('[data-period-sortable]').forEach((tableBody) => {
        let draggedRow = null;

        tableBody.addEventListener('dragstart', (event) => {
            const row = event.target.closest('tr');

            if (! row) {
                return;
            }

            draggedRow = row;
            row.classList.add('dragging');
            event.dataTransfer.effectAllowed = 'move';
        });

        tableBody.addEventListener('dragend', () => {
            if (draggedRow) {
                draggedRow.classList.remove('dragging');
                draggedRow = null;
                submitPeriodOrder(tableBody);
            }
        });

        tableBody.addEventListener('dragover', (event) => {
            if (! draggedRow) {
                return;
            }

            event.preventDefault();
            const afterElement = getDragAfterElement(tableBody, event.clientY, 'tr');

            if (afterElement === null) {
                tableBody.appendChild(draggedRow);
            } else {
                tableBody.insertBefore(draggedRow, afterElement);
            }
        });
    });
}

function initTimelineCardSorters() {
    document.querySelectorAll('[data-card-sortable]').forEach((board) => {
        let draggedCard = null;

        board.addEventListener('dragstart', (event) => {
            const card = event.target.closest('[data-card-id]');

            if (! card) {
                return;
            }

            draggedCard = card;
            card.classList.add('dragging');
            event.dataTransfer.effectAllowed = 'move';
        });

        board.addEventListener('dragend', () => {
            if (draggedCard) {
                draggedCard.classList.remove('dragging');
                draggedCard = null;
                submitCardOrder(board);
            }
        });

        board.querySelectorAll('[data-card-dropzone]').forEach((dropzone) => {
            dropzone.addEventListener('dragover', (event) => {
                if (! draggedCard) {
                    return;
                }

                event.preventDefault();
                const afterElement = getDragAfterElement(dropzone, event.clientY, '[data-card-id]');

                if (afterElement === null) {
                    dropzone.appendChild(draggedCard);
                } else {
                    dropzone.insertBefore(draggedCard, afterElement);
                }
            });

            dropzone.addEventListener('drop', (event) => {
                event.preventDefault();
            });
        });
    });
}

function submitPeriodOrder(container) {
    const endpoint = container.dataset.orderEndpoint;

    if (! endpoint) {
        return;
    }

    const items = Array.from(container.querySelectorAll('[data-item-id]')).map((row, index) => ({
        id: Number(row.dataset.itemId),
        position: index + 1,
    }));

    if (items.length === 0) {
        return;
    }

    postTimelineOrder(endpoint, items, container.dataset.orderError);
}

function submitCardOrder(board) {
    const endpoint = board.dataset.orderEndpoint;

    if (! endpoint) {
        return;
    }

    const items = [];

    board.querySelectorAll('[data-card-dropzone]').forEach((zone) => {
        const periodId = Number(zone.dataset.periodId);

        Array.from(zone.querySelectorAll('[data-card-id]')).forEach((card, index) => {
            items.push({
                id: Number(card.dataset.cardId),
                position: index + 1,
                period_id: periodId,
            });
        });
    });

    if (items.length === 0) {
        return;
    }

    postTimelineOrder(endpoint, items, board.dataset.orderError);
}

function postTimelineOrder(endpoint, items, errorMessage) {
    const token = document.head.querySelector('meta[name="csrf-token"]')?.content;

    fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...(token ? { 'X-CSRF-TOKEN': token } : {}),
        },
        body: JSON.stringify({ items }),
    }).catch(() => {
        window.alert(errorMessage || 'Failed to update the order. Please refresh and try again.');
    });
}

function getDragAfterElement(container, y, selector) {
    const elements = Array.from(container.querySelectorAll(`${selector}:not(.dragging)`));
    let closest = { offset: Number.NEGATIVE_INFINITY, element: null };

    elements.forEach((element) => {
        const rect = element.getBoundingClientRect();
        const offset = y - rect.top - rect.height / 2;

        if (offset < 0 && offset > closest.offset) {
            closest = { offset, element };
        }
    });

    return closest.element;
}
