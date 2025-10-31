import initFormFields from './formFields';
import initMediaField from './mediaField';
import initFieldSet from './fieldSet';
import initChipInputs from './chips';
import initOptionCards from './optionCards';
import initCustomSelects from './customSelects';
import initSlugFields from './slugFields';
import initPasswordToggle from './passwordToggle';

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
    initSlugFields(container);
    initMediaField(container);
    initFieldSet(container);
}
