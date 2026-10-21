import { BarController, BarElement, CategoryScale, Chart, LinearScale, Tooltip } from 'chart.js';

// Only the parts of Chart.js the dashboard uses, so the bundle stays small.
Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip);

/**
 * Draws a bar chart into every canvas with a data-chart attribute.
 *
 * Markup contract: data-chart holds JSON with "labels", "values", a "format" of
 * "currency" (values in cents, with a "currency" code) or "count", optional
 * "colors" (one CSS custom property name per bar) and "horizontal" (bars drawn
 * sideways, so long labels stay level in narrow panels). Colours and fonts come from
 * the design tokens, and animation is off when reduced motion is preferred.
 * A chart that cannot be drawn marks its .chart-figure as unavailable, which
 * shows its figures as a table instead (see resources/css/pages/dashboard.css).
 */
export function initCharts(root = document) {
    const canvases = root.querySelectorAll('canvas[data-chart]');

    if (canvases.length === 0) {
        return;
    }

    const styles = getComputedStyle(document.documentElement);
    const token = (name) => styles.getPropertyValue(name).trim();
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const locale = document.documentElement.lang || 'en';

    Chart.defaults.font.family = token('--font-sans');
    Chart.defaults.color = token('--color-text-secondary');

    canvases.forEach((canvas) => {
        try {
            drawChart(canvas, JSON.parse(canvas.dataset.chart), { token, reduceMotion, locale });
        } catch {
            // Shows this chart's figures as a table instead.
            canvas.closest('.chart-figure')?.classList.add('is-unavailable');
        }
    });
}

function drawChart(canvas, data, { token, reduceMotion, locale }) {
    const isMoney = data.format === 'currency';
    // Cents become euros here, for display only.
    const values = isMoney ? data.values.map((cents) => cents / 100) : data.values;
    const money = (fractionDigits) => new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: data.currency,
        minimumFractionDigits: fractionDigits,
        maximumFractionDigits: fractionDigits,
    });
    const tooltipFormat = isMoney ? money(2) : new Intl.NumberFormat(locale);
    const axisFormat = isMoney ? money(0) : new Intl.NumberFormat(locale);
    const valueAxis = data.horizontal ? 'x' : 'y';
    const categoryAxis = data.horizontal ? 'y' : 'x';

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                data: values,
                backgroundColor: (data.colors ?? data.values.map(() => '--color-accent')).map(token),
                borderRadius: 2,
                maxBarThickness: 44,
            }],
        },
        options: {
            indexAxis: categoryAxis,
            responsive: true,
            maintainAspectRatio: false,
            animation: reduceMotion ? false : { duration: 220 },
            plugins: {
                tooltip: {
                    displayColors: false,
                    callbacks: { label: (context) => tooltipFormat.format(context.parsed[valueAxis]) },
                },
            },
            scales: {
                [categoryAxis]: {
                    grid: { display: false },
                    border: { color: token('--color-border') },
                },
                [valueAxis]: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: token('--color-border') },
                    ticks: { precision: 0, callback: (value) => axisFormat.format(value) },
                },
            },
        },
    });
}
