/**
 * Off-canvas navigation drawer for viewports below the desktop breakpoint.
 *
 * While open, the drawer behaves as a modal dialog: the rest of the page is
 * made inert (which keeps keyboard focus inside the drawer), Escape or the
 * backdrop closes it, and focus returns to the control that opened it.
 */
const OPEN_CLASS = 'is-sidebar-open';
const DESKTOP_QUERY = '(width >= 64em)';

export function initSidebarDrawer(root = document) {
    const sidebar = root.querySelector('[data-sidebar]');
    const background = root.querySelectorAll('[data-drawer-inert]');

    if (!sidebar || background.length === 0 || sidebar.dataset.drawerReady) {
        return;
    }

    sidebar.dataset.drawerReady = 'true';

    const html = document.documentElement;
    const openers = root.querySelectorAll('[data-sidebar-open]');
    const closers = root.querySelectorAll('[data-sidebar-close]');
    const desktop = window.matchMedia(DESKTOP_QUERY);
    let returnFocusTo = null;

    const isOpen = () => html.classList.contains(OPEN_CLASS);

    const setOpenState = (open) => {
        html.classList.toggle(OPEN_CLASS, open);
        background.forEach((element) => {
            element.inert = open;
        });
        openers.forEach((button) => button.setAttribute('aria-expanded', String(open)));

        if (open) {
            sidebar.setAttribute('role', 'dialog');
            sidebar.setAttribute('aria-modal', 'true');
            sidebar.setAttribute('aria-label', 'Navigation');
        } else {
            sidebar.removeAttribute('role');
            sidebar.removeAttribute('aria-modal');
            sidebar.removeAttribute('aria-label');
        }
    };

    const open = () => {
        if (desktop.matches || isOpen()) {
            return;
        }

        returnFocusTo = document.activeElement;
        setOpenState(true);
        sidebar.querySelector('[data-sidebar-close]')?.focus();
    };

    const close = ({ restoreFocus = true } = {}) => {
        if (!isOpen()) {
            return;
        }

        setOpenState(false);

        if (restoreFocus && returnFocusTo instanceof HTMLElement) {
            returnFocusTo.focus();
        }

        returnFocusTo = null;
    };

    openers.forEach((button) => button.addEventListener('click', open));
    closers.forEach((button) => button.addEventListener('click', () => close()));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isOpen()) {
            event.preventDefault();
            close();
        }
    });

    // Following a link inside the drawer closes it before the page changes.
    sidebar.addEventListener('click', (event) => {
        if (event.target.closest('a[href]')) {
            close({ restoreFocus: false });
        }
    });

    // Growing into the desktop layout leaves the persistent sidebar in place.
    desktop.addEventListener('change', (event) => {
        if (event.matches) {
            close({ restoreFocus: false });
        }
    });
}
