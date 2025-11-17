/**
 * Ace Editor Code Editor Integration
 *
 * Professional code editor with syntax highlighting.
 * Loaded as part of editors.js bundle.
 */

import ace from 'ace-builds/src-noconflict/ace';
import 'ace-builds/src-noconflict/mode-html';
import 'ace-builds/src-noconflict/mode-css';
import 'ace-builds/src-noconflict/mode-javascript';
import 'ace-builds/src-noconflict/mode-json';
import 'ace-builds/src-noconflict/mode-xml';
import 'ace-builds/src-noconflict/theme-monokai';
import 'ace-builds/src-noconflict/theme-chrome';
import 'ace-builds/src-noconflict/theme-github';

/**
 * Get Ace mode based on language name
 */
function getAceMode(lang) {
    switch (lang) {
        case 'html':
            return 'ace/mode/html';
        case 'css':
            return 'ace/mode/css';
        case 'javascript':
        case 'js':
            return 'ace/mode/javascript';
        case 'json':
            return 'ace/mode/json';
        case 'xml':
            return 'ace/mode/xml';
        default:
            return 'ace/mode/html';
    }
}

/**
 * Get Ace theme based on theme name
 */
function getAceTheme(theme) {
    switch (theme) {
        case 'dark':
        case 'monokai':
            return 'ace/theme/monokai';
        case 'github':
            return 'ace/theme/github';
        case 'light':
        case 'chrome':
        default:
            return 'ace/theme/chrome';
    }
}

/**
 * Initialize a single code editor instance
 */
function initializeEditor(textarea) {
    const wrapper = textarea.closest('.code-editor-wrapper');
    if (!wrapper) return;

    const editorTarget = wrapper.querySelector('.code-editor-content');
    if (!editorTarget) return;

    // Clear any existing content in editor target (e.g. from cloned templates)
    editorTarget.innerHTML = '';

    // Get configuration from data attributes
    const config = {
        language: textarea.dataset.language || 'html',
        height: parseInt(textarea.dataset.height || '400', 10),
        autoHeight: textarea.dataset.autoHeight === 'true',
        theme: textarea.dataset.theme || 'light',
        lineNumbers: textarea.dataset.lineNumbers !== 'false',
        codeFolding: textarea.dataset.codeFolding !== 'false',
        tabSize: parseInt(textarea.dataset.tabSize || '2', 10),
    };

    // Create editor div
    const editorDiv = document.createElement('div');
    editorDiv.style.width = '100%';

    // Set height (auto or fixed)
    if (config.autoHeight) {
        editorDiv.style.height = 'auto';
        editorDiv.style.minHeight = `${Math.max(config.height, 200)}px`;
    } else {
        editorDiv.style.height = `${Math.max(config.height, 50)}px`;
    }

    editorTarget.appendChild(editorDiv);

    // Initialize Ace editor
    const editor = ace.edit(editorDiv);

    // Set initial value
    editor.setValue(textarea.value || '', -1); // -1 moves cursor to start

    // Configure editor
    const aceTheme = getAceTheme(config.theme);
    editor.setTheme(aceTheme);
    editor.session.setMode(getAceMode(config.language));
    editor.setShowPrintMargin(false);
    editor.renderer.setShowGutter(config.lineNumbers);
    editor.setOption('wrap', true);
    editor.setOption('indentedSoftWrap', false);
    editor.session.setTabSize(config.tabSize);
    editor.session.setUseSoftTabs(true);
    editor.setHighlightActiveLine(true);

    // Disable workers to avoid 404 errors for worker files
    editor.session.setUseWorker(false);

    // Code folding
    if (config.codeFolding) {
        editor.session.setFoldStyle('markbegin');
    }

    // Auto-height: adjust height based on content
    if (config.autoHeight) {
        const updateHeight = () => {
            const lines = editor.session.getLength();
            const lineHeight = editor.renderer.lineHeight;
            const newHeight = Math.max(lines * lineHeight + 5, Math.max(config.height, 200));
            editorDiv.style.height = newHeight + 'px';
            editor.resize();
        };

        // Update on content change
        editor.session.on('change', () => {
            textarea.value = editor.getValue();
            updateHeight();
        });

        // Initial height
        updateHeight();
    } else {
        // Sync content back to textarea on change (fixed height)
        editor.session.on('change', function() {
            textarea.value = editor.getValue();
        });
    }

    // Store editor instance on textarea element
    textarea._codeEditor = editor;

    // Handle form submission
    const form = textarea.closest('form');
    if (form) {
        form.addEventListener('submit', () => {
            textarea.value = editor.getValue();
        });
    }

    return editor;
}

/**
 * Destroy editor instance
 */
function destroyEditor(textarea) {
    if (textarea._codeEditor) {
        textarea._codeEditor.destroy();
        delete textarea._codeEditor;
    }
}

/**
 * Main initialization function
 * @param {HTMLElement|Document} container - Container to search for code editor fields (default: document)
 */
export default function initCodeEditor(container = document) {
    // Find all code editor fields within container
    const editorFields = container.querySelectorAll('.code-editor-field');

    if (editorFields.length === 0) {
        return;
    }

    // Initialize each editor
    editorFields.forEach(textarea => {
        // Skip if already initialized
        if (textarea._codeEditor) return;

        // Initialize editor
        initializeEditor(textarea);
    });
}

/**
 * Export utilities for programmatic use
 */
export {
    initializeEditor,
    destroyEditor,
    getAceMode,
    getAceTheme,
};
