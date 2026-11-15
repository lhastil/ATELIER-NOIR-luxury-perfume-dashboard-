/**
 * Live results for the top bar search.
 *
 * Markup contract: a form[data-global-search] (the normal GET search form) holding
 * input[name="q"], a [data-global-search-panel] for the results and a
 * [data-global-search-status] live region. While typing, the results are fetched
 * from the form's action as an XMLHttpRequest, which returns only the results
 * partial. Pressing Enter still submits the form, so search works without this
 * script or when a request fails.
 */
const DELAY = 250;
const MIN_LENGTH = 2;

export function initGlobalSearch(root = document) {
    const form = root.querySelector('[data-global-search]');
    const input = form?.querySelector('input[name="q"]');
    const panel = form?.querySelector('[data-global-search-panel]');
    const status = form?.querySelector('[data-global-search-status]');

    if (!form || !input || !panel || !status) {
        return;
    }

    let timer = null;
    let request = null;

    const links = () => [...panel.querySelectorAll('a')];

    const close = () => {
        panel.hidden = true;
    };

    const clear = () => {
        request?.abort();
        close();
        panel.innerHTML = '';
        status.textContent = '';
    };

    const load = async (query) => {
        request?.abort();
        request = new AbortController();

        try {
            const url = new URL(form.action);
            url.searchParams.set('q', query);

            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                credentials: 'same-origin',
                signal: request.signal,
            });

            // A failed request or an expired session (redirected to the login page)
            // shows no dropdown; Enter still opens the full results page.
            if (!response.ok || response.redirected) {
                clear();
                return;
            }

            // Server-rendered Blade partial from this site; every value in it is escaped.
            panel.innerHTML = await response.text();
            panel.hidden = false;

            const count = Number(panel.querySelector('[data-search-count]')?.dataset.searchCount ?? 0);
            status.textContent = count === 0
                ? 'No results.'
                : `${count} ${count === 1 ? 'result' : 'results'} below the search field. Press the down arrow to reach them.`;
        } catch (error) {
            if (error.name !== 'AbortError') {
                clear();
            }
        }
    };

    input.addEventListener('input', () => {
        clearTimeout(timer);
        const query = input.value.trim();

        if (query.length < MIN_LENGTH) {
            clear();
            return;
        }

        timer = setTimeout(() => load(query), DELAY);
    });

    // Coming back to the field shows the previous results again.
    input.addEventListener('focus', () => {
        if (panel.innerHTML.trim() !== '' && input.value.trim().length >= MIN_LENGTH) {
            panel.hidden = false;
        }
    });

    form.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) {
            event.preventDefault();
            close();
            input.focus();
            return;
        }

        if (panel.hidden || (event.key !== 'ArrowDown' && event.key !== 'ArrowUp')) {
            return;
        }

        // The arrow keys move between the input and the result links.
        const items = links();
        if (items.length === 0) {
            return;
        }

        event.preventDefault();
        const index = items.indexOf(document.activeElement);

        if (event.key === 'ArrowDown') {
            items[Math.min(index + 1, items.length - 1)].focus();
        } else if (index <= 0) {
            input.focus();
        } else {
            items[index - 1].focus();
        }
    });

    // Closes when focus or a click leaves the search.
    form.addEventListener('focusout', (event) => {
        if (!form.contains(event.relatedTarget)) {
            close();
        }
    });

    document.addEventListener('click', (event) => {
        if (!form.contains(event.target)) {
            close();
        }
    });
}
