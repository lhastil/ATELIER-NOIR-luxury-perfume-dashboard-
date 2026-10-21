/**
 * Horizontally scrollable regions (wide tables on small screens).
 *
 * The server renders them keyboard-focusable so they can always be scrolled
 * without a mouse. Once measured, the tab stop is kept only while the content
 * actually overflows, re-evaluated whenever the region or its content is
 * resized and once web fonts have loaded.
 */
export function initScrollRegions(root = document) {
    const regions = [...root.querySelectorAll('[data-scroll-region]:not([data-scroll-region-ready])')];

    if (regions.length === 0) {
        return;
    }

    const update = (region) => {
        if (region.scrollWidth > region.clientWidth + 1) {
            region.tabIndex = 0;
        } else {
            region.removeAttribute('tabindex');
        }
    };

    const observer =
        'ResizeObserver' in window
            ? new ResizeObserver((entries) => entries.forEach((entry) => update(entry.target.closest('[data-scroll-region]'))))
            : null;

    regions.forEach((region) => {
        region.dataset.scrollRegionReady = 'true';
        update(region);
        observer?.observe(region);

        if (region.firstElementChild) {
            observer?.observe(region.firstElementChild);
        }
    });

    document.fonts?.ready.then(() => regions.forEach(update));
}
