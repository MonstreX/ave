import initFormFields from './formFields';
import initMediaField, { updateAllMediaHiddenInputs } from './mediaField';
import initFieldSet from './fieldSet';
import initFieldsetCards from './fieldsetCards';
import initChipInputs from './chips';
import initOptionCards from './optionCards';
import initCustomSelects from './customSelects';
import initSlugFields from './slugFields';
import initPasswordToggle from './passwordToggle';
import initToggleBootstrap from './toggleBootstrap';
import initTags from './tags';
import initFileUpload from './fileUpload';

// NOTE: Editors (richEditor, codeEditor) are now loaded separately via editors.js
// which is only included on pages that have editor fields

/**
 * Initialize all form components
 * @param {HTMLElement|Document} container - Container to search for form fields (default: document)
 */
export default function initForms(container = document) {
    initFormFields(container);
    initChipInputs(container);
    initOptionCards(container);
    initCustomSelects(container);
    initPasswordToggle(container);
    initToggleBootstrap(container);
    initSlugFields(container);
    initTags(container);
    initFileUpload(container);
    initMediaField(container);
    initFieldSet(container);
    initFieldsetCards(container);

    // Add form submit handler to update all media hidden inputs before submission
    container.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', (e) => {
            // Update all media field hidden inputs before form submission
            updateAllMediaHiddenInputs();

            // Log all form data for debugging
            const formData = new FormData(form);
            for (let [key, value] of formData.entries()) {
                if (key.includes('attachment') || key.includes('featured') || key.includes('file')) {
                }
            }
        });
    });
}
