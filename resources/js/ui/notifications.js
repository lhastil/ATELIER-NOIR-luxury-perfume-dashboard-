/**
 * Read state in the notification dropdown, without reloading the page:
 * "Mark all as read", and marking one notification as read when it is opened.
 *
 * Markup contract (resources/views/components/admin/topbar.blade.php): a
 * [data-notifications] dropdown holding the [data-notifications-read-all] form,
 * the [data-notifications-badge] on the bell, unread items as links with a
 * [data-notification-read] address and [data-notification-unread] dot and
 * "(unread)" label, an error [data-notifications-error] and a screen-reader
 * [data-notifications-status]. Requests are sent as JSON with the form's CSRF
 * field; the page only changes once the server confirms. Without this script the
 * form still submits normally and the links simply open their pages.
 */
const ERROR_MESSAGE = 'The notifications could not be marked as read. Please try again.';

const jsonHeaders = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };

export function initNotifications(root = document) {
    const container = root.querySelector('[data-notifications]');

    if (!container) {
        return;
    }

    const form = container.querySelector('[data-notifications-read-all]');
    const button = form?.querySelector('button[type="submit"]');
    const trigger = container.querySelector('[data-dropdown-trigger]');
    const error = container.querySelector('[data-notifications-error]');
    const status = container.querySelector('[data-notifications-status]');
    let busy = false;

    const setBusy = (value) => {
        busy = value;
        // aria-disabled instead of disabled, so a keyboard user keeps focus on the button.
        button?.setAttribute('aria-disabled', String(value));
    };

    const showError = () => {
        if (error) {
            error.textContent = ERROR_MESSAGE;
            error.hidden = false;
        }
    };

    // The bell's badge and label show the count the server returned.
    const setUnreadCount = (count) => {
        const badge = container.querySelector('[data-notifications-badge]');

        if (count > 0) {
            if (badge) {
                badge.textContent = count > 9 ? '9+' : String(count);
            }
            trigger?.setAttribute('aria-label', `Notifications, ${count} unread`);
        } else {
            badge?.remove();
            trigger?.setAttribute('aria-label', 'Notifications');
        }
    };

    const showAsRead = (item) => {
        item.classList.remove('notification--unread');
        item.removeAttribute('data-notification-read');
        item.querySelectorAll('[data-notification-unread]').forEach((element) => element.remove());
    };

    const markAllRead = () => {
        setUnreadCount(0);
        container.querySelectorAll('.notification').forEach(showAsRead);

        // The button is about to disappear: keep focus inside the open dropdown.
        (container.querySelector('.notification') ?? trigger)?.focus();
        form.remove();

        if (status) {
            status.textContent = 'All notifications marked as read.';
        }
    };

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();

        // Ignores repeated clicks while the request is in progress.
        if (busy) {
            return;
        }

        setBusy(true);
        if (error) {
            error.hidden = true;
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: jsonHeaders,
                credentials: 'same-origin',
            });

            // An expired session (401) or page (419) or any other failure leaves the UI unread.
            if (!response.ok || response.redirected) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            markAllRead();
        } catch {
            showError();
            setBusy(false);
        }
    });

    // Opening an unread notification: mark it as read, then go to its page.
    container.addEventListener('click', async (event) => {
        const item = event.target.closest('a[data-notification-read]');

        // Read items, and Ctrl/Cmd/Shift-clicks or middle clicks (new tab or window), open as usual.
        if (!item || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        event.preventDefault();

        if (item.dataset.notificationBusy) {
            return;
        }
        item.dataset.notificationBusy = 'true';

        try {
            const body = new FormData();
            body.append('_token', form?.querySelector('input[name="_token"]')?.value ?? '');

            const response = await fetch(item.dataset.notificationRead, {
                method: 'POST',
                body,
                headers: jsonHeaders,
                credentials: 'same-origin',
            });

            if (!response.ok || response.redirected) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            const { unread_count: count } = await response.json();

            showAsRead(item);
            setUnreadCount(Number(count));
            if (Number(count) === 0) {
                form?.remove();
            }
        } catch {
            // Not marked as read: the badge and dot stay as they are, and the page still opens.
        }

        window.location.assign(item.href);
    });
}
