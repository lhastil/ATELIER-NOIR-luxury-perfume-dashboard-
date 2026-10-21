/**
 * Asks for confirmation before submitting any form with a data-confirm message
 * (used for deleting products).
 */
export function initConfirmSubmit(root = document) {
    root.addEventListener('submit', (event) => {
        const form = event.target.closest?.('form[data-confirm]');

        if (form && !window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
}
