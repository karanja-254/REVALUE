import './bootstrap';

import Alpine from 'alpinejs';

import { initLogisticsMaps } from './logistics/map.js';
import { initDriverTracker } from './logistics/driver-tracker.js';

window.Alpine = Alpine;

Alpine.start();

// Maps / Logistics (Person 4) front-end bootstrapping.
function initLogistics() {
    initLogisticsMaps();
    initDriverTracker();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLogistics);
} else {
    initLogistics();
}
