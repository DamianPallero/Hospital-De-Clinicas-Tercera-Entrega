document.addEventListener('DOMContentLoaded', () => {
    const formLogin = document.getElementById('loginForm');
    if (formLogin) {
        formLogin.addEventListener('submit', (evento) => {
            let valido = true;
            const correo = document.getElementById('email');
            const clave = document.getElementById('password');
            const respuesta = document.getElementById('captcha');
            const ayudaCorreo = document.getElementById('emailHelp');
            const ayudaClave = document.getElementById('passHelp');
            const ayudaCaptcha = document.getElementById('captchaHelp');

            ayudaCorreo.textContent = '';
            ayudaClave.textContent = '';
            ayudaCaptcha.textContent = '';
            correo.classList.remove('input-error');
            clave.classList.remove('input-error');
            respuesta.classList.remove('input-error');
            correo.setAttribute('aria-invalid', 'false');
            clave.setAttribute('aria-invalid', 'false');
            respuesta.setAttribute('aria-invalid', 'false');

            const patronCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!correo.value.trim() || !patronCorreo.test(correo.value.trim())) {
                ayudaCorreo.textContent = 'Ingrese un correo institucional válido.';
                correo.classList.add('input-error');
                correo.setAttribute('aria-invalid', 'true');
                valido = false;
            }

            if (!clave.value) {
                ayudaClave.textContent = 'La contraseña es obligatoria.';
                clave.classList.add('input-error');
                clave.setAttribute('aria-invalid', 'true');
                valido = false;
            }

            if (!respuesta.value.trim()) {
                ayudaCaptcha.textContent = 'Resuelva la operación matemática.';
                respuesta.classList.add('input-error');
                respuesta.setAttribute('aria-invalid', 'true');
                valido = false;
            }

            if (!valido) evento.preventDefault();
        });
    }
});