export default function initLoginPage() {
    if (!document.body.classList.contains('ave-login')) {
        return;
    }

    const form = document.forms[0];
    const submitButton = form?.querySelector('button[type="submit"]');
    const email = form?.querySelector('[name="email"]');
    const password = form?.querySelector('[name="password"]');

    if (!form || !submitButton || !email || !password) {
        return;
    }

    submitButton.addEventListener('click', (event) => {
        if (form.checkValidity()) {
            const signingin = submitButton.querySelector('.signingin');
            const signin = submitButton.querySelector('.signin');

            if (signingin && signin) {
                signingin.classList.remove('hidden');
                signin.classList.add('hidden');
            }
        } else {
            event.preventDefault();
        }
    });

    email.focus();
    const emailGroup = document.getElementById('emailGroup');
    if (emailGroup) {
        emailGroup.classList.add('focused');
    }
}



