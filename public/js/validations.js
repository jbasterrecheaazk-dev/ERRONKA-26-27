// Módulo de validación de formularios de login y registro
// Se ejecuta al cargar la página y adjunta validadores a los formularios
//
// Formularios gestionados:
//   - #login (POST /controllers/AuthController.php?action=login)
//   - #register (POST /controllers/AuthController.php?action=registro)
//
// Reglas de validación:
//   Login: email no vacío + formato válido, contraseña no vacía
//   Registro: nombre no vacío, email no vacío + formato válido,
//             contraseña no vacía + mínimo 8 caracteres

document.addEventListener('DOMContentLoaded', () => {
    // Seleccionamos ambos formularios: login y registro
    const loginForm = document.getElementById('login');
    const registerForm = document.getElementById('register');

    // Expresión regular simple pero robusta para validar formato de email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    /**
     * Mostrar mensaje de error debajo de un input
     * Añade clase .input--error (borde rojo) y crea/actualiza un elemento .form__error
     *
     * @param {HTMLInputElement} inputElement - Campo que contiene el error
     * @param {string} message - Mensaje de error a mostrar
     */
    function showError(inputElement, message) {
        // Marcamos el input con borde rojo (siguiendo estilo brutalista)
        inputElement.classList.add('input--error');

        // Comprobamos si ya existe un elemento de error para no duplicarlo
        let errorDiv = inputElement.parentElement.querySelector('.form__error');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'form__error';
            inputElement.parentElement.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
    }

    /**
     * Limpiar el error visual de un input
     * Quita clase .input--error y elimina el elemento .form__error
     *
     * @param {HTMLInputElement} inputElement - Campo a limpiar
     */
    function clearError(inputElement) {
        inputElement.classList.remove('input--error');
        const errorDiv = inputElement.parentElement.querySelector('.form__error');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    // ==========================================
    // VALIDACIÓN FORMULARIO DE LOGIN
    // ==========================================
    if (loginForm) {
        loginForm.addEventListener('submit', (event) => {
            let isValid = true;

            const emailInput = loginForm.querySelector('input[name="email"]');
            const passInput = loginForm.querySelector('input[name="pasahitza"]');

            // 1. Validar email vacío o formato incorrecto
            if (!emailInput.value.trim()) {
                showError(emailInput, 'El correo electrónico es obligatorio.');
                isValid = false;
            } else if (!emailRegex.test(emailInput.value.trim())) {
                showError(emailInput, 'Introduce un formato de correo válido (ej. usuario@domain.com).');
                isValid = false;
            } else {
                clearError(emailInput);
            }

            // 2. Validar contraseña vacía
            if (!passInput.value) {
                showError(passInput, 'La contraseña es obligatoria.');
                isValid = false;
            } else {
                clearError(passInput);
            }

            // Si la validación falla, detenemos el envío del formulario
            if (!isValid) {
                event.preventDefault();
            }
        });
    }

    // ==========================================
    // VALIDACIÓN FORMULARIO DE REGISTRO
    // ==========================================
    if (registerForm) {
        registerForm.addEventListener('submit', (event) => {
            let isValid = true;

            const emailInput = registerForm.querySelector('input[name="email"]');
            const passInput = registerForm.querySelector('input[name="pasahitza"]');
            const nameInput = registerForm.querySelector('input[name="izena"]');

            // 1. Validar nombre no vacío
            if (!nameInput.value.trim()) {
                showError(nameInput, 'El nombre es obligatorio.');
                isValid = false;
            } else {
                clearError(nameInput);
            }

            // 2. Validar email
            if (!emailInput.value.trim()) {
                showError(emailInput, 'El correo electrónico es obligatorio.');
                isValid = false;
            } else if (!emailRegex.test(emailInput.value.trim())) {
                showError(emailInput, 'Introduce un formato de correo válido.');
                isValid = false;
            } else {
                clearError(emailInput);
            }

            // 3. Validar contraseña (obligatoria y mínimo 8 caracteres)
            if (!passInput.value) {
                showError(passInput, 'La contraseña es obligatoria.');
                isValid = false;
            } else if (passInput.value.length < 8) {
                showError(passInput, 'La contraseña debe tener al menos 8 caracteres.');
                isValid = false;
            } else {
                clearError(passInput);
            }

            // Si falla alguna regla, prevenimos el envío
            if (!isValid) {
                event.preventDefault();
            }
        });
    }
});
