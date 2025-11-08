/**
 * Password Toggle Module
 * Toggles password visibility by changing input.type between 'password' and 'text'
 */

export default function initPasswordToggle(root = document) {
    const toggleButtons = root.querySelectorAll('[data-password-toggle]');

    toggleButtons.forEach((button) => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            const wrapper = this.closest('.password-input-wrapper');
            const input = wrapper?.querySelector('[data-password-input]');
            const iconEye = this.querySelector('.icon-eye');
            const iconEyeOff = this.querySelector('.icon-eye-off');

            if (!wrapper || !input) {
                return;
            }

            // Toggle between password and text
            if (input.type === 'password') {
                input.type = 'text';
                wrapper.classList.add('password-shown');
                if (iconEye) iconEye.classList.add('hidden');
                if (iconEyeOff) iconEyeOff.classList.remove('hidden');
            } else {
                input.type = 'password';
                wrapper.classList.remove('password-shown');
                if (iconEye) iconEye.classList.remove('hidden');
                if (iconEyeOff) iconEyeOff.classList.add('hidden');
            }
        }, true); // Capture phase - execute before bubbling
    });
}
