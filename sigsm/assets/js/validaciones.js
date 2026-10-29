document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            let isValid = true;
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            const captcha = document.getElementById('captcha');
            const emailHelp = document.getElementById('emailHelp');
            const passHelp = document.getElementById('passHelp');
            const captchaHelp = document.getElementById('captchaHelp');

            emailHelp.textContent = '';
            passHelp.textContent = '';
            captchaHelp.textContent = '';
            email.classList.remove('input-error');
            password.classList.remove('input-error');
            captcha.classList.remove('input-error');
            email.setAttribute('aria-invalid', 'false');
            password.setAttribute('aria-invalid', 'false');
            captcha.setAttribute('aria-invalid', 'false');

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email.value.trim() || !emailRegex.test(email.value.trim())) {
                emailHelp.textContent = 'Ingrese un correo institucional válido.';
                email.classList.add('input-error');
                email.setAttribute('aria-invalid', 'true');
                isValid = false;
            }

            if (!password.value) {
                passHelp.textContent = 'La contraseña es obligatoria.';
                password.classList.add('input-error');
                password.setAttribute('aria-invalid', 'true');
                isValid = false;
            }

            if (!captcha.value.trim()) {
                captchaHelp.textContent = 'Resuelva la operación matemática.';
                captcha.classList.add('input-error');
                captcha.setAttribute('aria-invalid', 'true');
                isValid = false;
            }

            if (!isValid) e.preventDefault();
        });
    }
});