import { initConfirmSubmit } from './ui/confirm-submit.js';
import { initDropdowns } from './ui/dropdown.js';
import { initImagePreviews } from './ui/image-preview.js';
import { initScrollRegions } from './ui/scroll-region.js';
import { initSidebarDrawer } from './ui/sidebar-drawer.js';

initSidebarDrawer();
initDropdowns();
initScrollRegions();
initImagePreviews();
initConfirmSubmit();

// Chart.js is loaded only on pages that draw a chart, such as the dashboard. If it
// cannot be loaded, each chart's figures are shown as a table instead.
if (document.querySelector('canvas[data-chart]')) {
    import('./ui/charts.js')
        .then(({ initCharts }) => initCharts())
        .catch(() => document.querySelectorAll('.chart-figure').forEach((figure) => figure.classList.add('is-unavailable')));
}

// Tells the inline fallback in the layout that the interactive navigation is ready.
document.documentElement.classList.add('js-ready');
