import { Jodit } from 'jodit';
import 'jodit/esm/plugins/source/source.js';

// Import Ace Editor
import ace from 'ace-builds/src-noconflict/ace';
import 'ace-builds/src-noconflict/mode-html';
import 'ace-builds/src-noconflict/theme-monokai';

// Make Ace available globally for Jodit
window.ace = ace;

/**
 * Initialize Rich Editor (Jodit) instances
 * @param {HTMLElement|Document} container - Container to search for rich editor fields (default: document)
 */
export default function initRichEditor(container = document) {
    const editors = container.querySelectorAll('textarea[data-editor="rich"]');

    if (editors.length === 0) {
        return;
    }
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const adminPrefix = document.querySelector('meta[name="ave-route-prefix"]').getAttribute('content');

    editors.forEach(function (editor) {
        if (editor.id) {
            const element = document.getElementById(editor.id);
            if(element){
                // Find the closest form to get model context
                const form = editor.closest('form');
                const modelType = form ? form.dataset.modelType : null;
                const modelId = form ? form.dataset.modelId : null;
                const fieldName = editor.name; // Field name for collection

                // Parse preset config if provided
                let presetConfig = {};
                if (editor.dataset.config) {
                    try {
                        presetConfig = JSON.parse(editor.dataset.config);
                    } catch (e) {
                        console.error('Failed to parse editor config:', e);
                    }
                }

                // Default configuration
                const defaultConfig = {
                    height: editor.dataset.height || 400,

                    // Use Ace Editor for source mode with Monokai theme
                    sourceEditor: 'ace',
                    sourceEditorNativeOptions: {
                        theme: 'ace/theme/monokai',
                        mode: 'ace/mode/html',
                        showGutter: true,
                        showPrintMargin: false,
                        highlightActiveLine: true,
                        wrap: true,
                        useWorker: false, // Disable workers to avoid 404 errors
                    },

                    buttons: [
                        'bold', 'italic', 'underline', 'strikethrough', '|',
                        'ul', 'ol', '|',
                        'font', 'fontsize', 'brush', 'paragraph', '|',
                        'image', 'link', 'table', '|',
                        'undo', 'redo', '|',
                        'source'
                    ],

                    // Uploader config for image button
                    uploader: {
                        url: `/${adminPrefix}/media/upload`,
                        format: 'json',
                        insertImageAsBase64URI: false,

                        // Build form data
                        buildData: function (data) {
                            const formData = new FormData();

                            // Check if data has files
                            if (data instanceof FormData) {
                                // Try different file parameter names
                                let file = null;

                                // Check files[0] (Jodit default)
                                file = data.get('files[0]');

                                // Check files[] (fallback)
                                if (!file) {
                                    const filesArray = data.getAll('files[]');
                                    if (filesArray.length > 0) {
                                        file = filesArray[0];
                                    }
                                }

                                // Check files (fallback)
                                if (!file) {
                                    file = data.get('files');
                                }

                                if (file) {
                                    formData.append('image', file);
                                }
                            } else if (data && data.files) {
                                if (data.files[0]) {
                                    formData.append('image', data.files[0]);
                                }
                            }

                            // Add CSRF token
                            formData.append('_token', csrfToken);

                            // Add model context if available (for binding image to model record)
                            if (modelType) {
                                formData.append('model_type', modelType);
                            }
                            if (modelId) {
                                formData.append('model_id', modelId);
                            }
                            if (fieldName) {
                                formData.append('collection', fieldName);
                            }

                            return formData;
                        },

                        // Process server response
                        isSuccess: function (resp) {
                            return resp && resp.success;
                        },

                        getMessage: function (resp) {
                            return (resp && resp.message) ? resp.message : '';
                        },

                        process: function (resp) {
                            // Our controller returns: {success: true, data: {url: '...'}}
                            if (resp && resp.success && resp.data && resp.data.url) {
                                // Jodit expects this exact format for images
                                return {
                                    files: [resp.data.url],
                                    isImages: [true],
                                    path: '',
                                    baseurl: '',
                                    error: 0,
                                    msg: ''
                                };
                            }

                            return {
                                files: [],
                                error: 1,
                                msg: (resp && resp.message) ? resp.message : 'Upload failed'
                            };
                        },

                        defaultHandlerError: function (resp) {
                            let message = 'Upload error';

                            if (resp && resp.message) {
                                message = resp.message;
                            } else if (resp && resp.data && resp.data.message) {
                                message = resp.data.message;
                            }

                            this.jodit.alert(message);
                        },

                        error: function (e) {
                            const message = (e && e.message) ? e.message : 'Upload error';
                            this.jodit.alert(message);
                        }
                    },

                    // Image dialog - only upload tab, no URL insert
                    filebrowser: {
                        ajax: {
                            url: `/${adminPrefix}/media/upload`
                        }
                    }
                };

                // Merge preset config on top of defaults
                // Deep merge for nested objects like options
                const finalConfig = {
                    ...defaultConfig,
                    ...presetConfig,
                    sourceEditorNativeOptions: {
                        ...defaultConfig.sourceEditorNativeOptions,
                        ...(presetConfig.sourceEditorNativeOptions || {})
                    },
                    uploader: {
                        ...defaultConfig.uploader,
                        ...(presetConfig.uploader || {})
                    },
                    filebrowser: {
                        ...defaultConfig.filebrowser,
                        ...(presetConfig.filebrowser || {})
                    }
                };

                new Jodit(element, finalConfig);
            }
        }
    });
}

