/**
 * Disclosure dropdowns (notifications, account menu).
 *
 * Markup contract: [data-dropdown] wraps a [data-dropdown-trigger] button whose
 * aria-controls points at a [data-dropdown-panel] element. Only one dropdown is
 * open at a time; Escape, an outside click or moving focus elsewhere closes it.
 */
export function initDropdowns(root = document) {
    const dropdowns = [...root.querySelectorAll('[data-dropdown]:not([data-dropdown-ready])')]
        .map((element) => ({
            element,
            trigger: element.querySelector('[data-dropdown-trigger]'),
            panel: element.querySelector('[data-dropdown-panel]'),
        }))
        .filter(({ trigger, panel }) => trigger && panel);

    if (dropdowns.length === 0) {
        return;
    }

    const setOpen = (dropdown, open) => {
        dropdown.panel.hidden = !open;
        dropdown.trigger.setAttribute('aria-expanded', String(open));
    };

    const isOpen = (dropdown) => !dropdown.panel.hidden;

    const closeAll = (except = null) => {
        dropdowns.filter((dropdown) => dropdown !== except && isOpen(dropdown)).forEach((dropdown) => setOpen(dropdown, false));
    };

    dropdowns.forEach((dropdown) => {
        dropdown.element.dataset.dropdownReady = 'true';

        dropdown.trigger.addEventListener('click', () => {
            const open = !isOpen(dropdown);
            closeAll(dropdown);
            setOpen(dropdown, open);
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-dropdown]')) {
            closeAll();
        }
    });

    // Focus landing anywhere outside an open dropdown closes it.
    document.addEventListener('focusin', (event) => {
        dropdowns.filter((dropdown) => isOpen(dropdown) && !dropdown.element.contains(event.target)).forEach((dropdown) => setOpen(dropdown, false));
    });

    // Escape closes the open dropdown wherever focus is (Safari does not focus clicked buttons).
    // Capture phase, so the drawer's own Escape handler does not also run.
    document.addEventListener(
        'keydown',
        (event) => {
            const open = dropdowns.find(isOpen);

            if (event.key === 'Escape' && open) {
                event.stopPropagation();
                setOpen(open, false);
                open.trigger.focus();
            }
        },
        true,
    );
}
