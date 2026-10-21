import { initDropdowns } from './ui/dropdown.js';
import { initSidebarDrawer } from './ui/sidebar-drawer.js';

initSidebarDrawer();
initDropdowns();

// Tells the inline fallback in the layout that the interactive navigation is ready.
document.documentElement.classList.add('js-ready');
