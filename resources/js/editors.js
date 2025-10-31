/**
 * Editors Bundle
 * Includes Jodit WYSIWYG and Ace Code Editor
 * Only loaded on pages with editor fields
 */

import initRichEditor from './modules/forms/richEditor';
import initCodeEditor from './modules/forms/codeEditor';

/**
 * Initialize all editors in a container
 * @param {HTMLElement|Document} container - Container to search for editor fields (default: document)
 */
export function initEditors(container = document) {
    initRichEditor(container);
    initCodeEditor(container);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    initEditors();
});

// Export individual initializers for direct use
export { initRichEditor, initCodeEditor };
