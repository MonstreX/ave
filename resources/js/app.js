import '../css/app.scss';

import { aveEvents } from './core/EventBus.js';
import initSidebar from './modules/layout/sidebar';
import initNavbar from './modules/layout/navbar';
import initForms from './modules/forms';
import initUI from './modules/ui';
import initLoginPage from './modules/pages/login';
import { setupFormReinitSubscriptions } from './modules/forms/formReinit.js';
import initResourceActions from './modules/resources/actions.js';
import initInlineEditing from './modules/resources/inlineEditing.js';
import initPagination from './modules/resources/pagination.js';
import initTreeView from './modules/resources/treeView.js';
import initSortableTable from './modules/resources/sortableTable.js';
import initSortableGroupedTable from './modules/resources/sortableGroupedTable.js';
import initFormValidation from './modules/forms/formValidation.js';

// Expose global event bus
window.Ave = window.Ave || {};
window.Ave.events = aveEvents;

document.addEventListener('DOMContentLoaded', () => {
    // Initialize form validation handler for hidden fields
    // Allows forms with hidden/collapsed fields to submit without browser validation errors
    initFormValidation();

    // Setup event-driven form component reinitialization
    // This allows components (fieldset, modals, AJAX loaders) to emit 'dom:updated'
    // and have form components automatically reinitialize
    setupFormReinitSubscriptions();

    initSidebar();
    initNavbar();
    initUI();
    initForms();
    initLoginPage();
    initResourceActions();
    initInlineEditing();
    initPagination();
    initSortableTable();
    initTreeView();
    initSortableGroupedTable();

    // Notify all listeners that DOM is ready and initialized
    // This allows dynamic content loaders to trigger reinitialization
    aveEvents.emit('dom:updated', document);
});

window.addEventListener('load', () => {
    const loader = document.getElementById('ave-loader');

    if (loader) {
        // Small delay to ensure smooth transition, then fade out
        setTimeout(() => {
            loader.style.opacity = '0';

            // Remove from DOM after fade animation completes
            setTimeout(() => {
                loader.style.display = 'none';
            }, 300);
        }, 200);
    }
});
