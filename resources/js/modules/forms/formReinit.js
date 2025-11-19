/**
 * Form Component Reinitialization
 *
 * Used to reinitialize form components in dynamically added containers
 * (e.g., new FieldSet items, dynamically loaded content)
 *
 * This module imports all form modules directly to avoid circular dependencies
 * between index.js and fieldSet.js
 *
 * Supports two modes:
 * 1. Direct call: reinitFormComponents(container)
 * 2. Event-driven: setupFormReinitSubscriptions() + aveEvents.emit('dom:updated', container)
 */

import { aveEvents } from '../../core/EventBus.js';
import initMediaField from './mediaField.js';
import initChipInputs from './chips.js';
import initCustomSelects from './customSelects.js';
import initSlugFields from './slugFields.js';
import initPasswordToggle from './passwordToggle.js';
import initFormFields from './formFields.js';
import initOptionCards from './optionCards.js';
import initFieldsetCards from './fieldsetCards.js';

let editorsModulePromise = null;

async function loadEditorsModule() {
    if (!editorsModulePromise) {
        editorsModulePromise = import('../../editors.js');
    }

    return editorsModulePromise;
}

/**
 * Reinitialize all form components in a container
 * @param {HTMLElement} container - Container to search for form fields
 */
export function reinitFormComponents(container) {
    initFormFields(container);
    initChipInputs(container);
    initOptionCards(container);
    initCustomSelects(container);
    initPasswordToggle(container);
    initSlugFields(container);
    initMediaField(container);
    initFieldsetCards(container);
}

/**
 * Reinitialize editor components in a container
 * @param {HTMLElement} container - Container to search for editor fields
 */
export async function reinitEditors(container) {
    let editorsModule = null;

    try {
        editorsModule = await loadEditorsModule();
    } catch (error) {
        console.error('Failed to load editors bundle', error);
        return;
    }

    const { initCodeEditor, initRichEditor } = editorsModule;

    if (typeof initCodeEditor === 'function') {
        initCodeEditor(container);
    }

    if (typeof initRichEditor === 'function') {
        initRichEditor(container);
    }
}

/**
 * Setup event-driven form component reinitialization
 *
 * Subscribes to the 'dom:updated' event and automatically reinitializes
 * all form components in the updated container.
 *
 * Call this once in app.js initialization to enable automatic reinitialization
 * whenever new form elements are added to the DOM.
 *
 * @example
 * // In app.js
 * import { setupFormReinitSubscriptions } from './modules/forms/formReinit.js';
 * setupFormReinitSubscriptions();
 *
 * // Later, when adding form elements:
 * aveEvents.emit('dom:updated', newContainer);
 */
export function setupFormReinitSubscriptions() {
    aveEvents.on('dom:updated', (container) => {
        // Reinitialize all form components
        reinitFormComponents(container);
        // Reinitialize editors
        reinitEditors(container);
    });
}
