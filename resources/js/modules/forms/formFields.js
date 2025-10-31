export default function initFormFields(root = document) {
    root.querySelectorAll('.form-field-default').forEach((field) => {
        const input = field.querySelector('input');
        if (!input) {
            return;
        }

        input.addEventListener('focus', () => field.classList.add('focused'));
        input.addEventListener('blur', () => field.classList.remove('focused'));
    });
}


