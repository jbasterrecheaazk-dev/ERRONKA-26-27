<?php
/**
 * VIEWS/PAGES/LOGIN.PHP
 *
 * Página de autenticación: formularios de login y registro en una sola vista.
 * Los formularios se alternan mediante tabs (botones superiores).
 *
 * Parámetros de URL soportados:
 *   ?success=registrado → muestra mensaje de registro exitoso
 *   ?error=...          → muestra mensaje de error traducido
 *
 * Submit de formularios:
 *   - Login: POST a /controllers/AuthController.php?action=login
 *   - Registro: POST a /controllers/AuthController.php?action=registro
 *
 * Validación cliente-side: validated.js se encarga de validar antes de enviar.
 */
// Capturar mensajes de éxito/error desde parámetros GET
$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoinuTruk — Acceso</title>
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>
    <main class="login">
        <!-- Mensaje de éxito tras registro -->
        <?php if ($success === 'registrado'): ?>
            <p>Registro completado. Ahora puedes iniciar sesión.</p>
        <?php endif; ?>
        <!-- Mensaje de error (traducido por el controlador) -->
        <?php if ($error): ?>
            <p>Error: <?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <!-- Tabs para alternar entre login y registro -->
        <div class="login__tabs">
            <button class="tab tab--active" data-target="login">Iniciar sesión</button>
            <button class="tab" data-target="register">Registrarse</button>
        </div>

        <!-- Formulario Login: envía a AuthController?action=login -->
        <!-- novalidate desactiva validación nativa del navegador (usamos validations.js) -->
        <form class="form form--active" id="login" method="POST" action="/controllers/AuthController.php?action=login" novalidate>
            <div class="form__group">
                <label class="form__label" for="email-login">Correo electrónico</label>
                <input class="input" id="email-login" type="email" name="email" required>
            </div>
            <div class="form__group">
                <label class="form__label" for="password-login">Contraseña</label>
                <input class="input" id="password-login" type="password" name="pasahitza" required>
            </div>
            <button class="btn btn--primary" type="submit">Entrar</button>
        </form>

        <!-- Formulario Registro: envía a AuthController?action=registro -->
        <!-- Campos: email, pasahitza, izena (nombre en euskera) -->
        <form class="form" id="register" method="POST" action="/controllers/AuthController.php?action=registro" novalidate>
            <div class="form__group">
                <label class="form__label" for="email-reg">Correo electrónico</label>
                <input class="input" id="email-reg" type="email" name="email" required>
            </div>
            <div class="form__group">
                <label class="form__label" for="password-reg">Contraseña</label>
                <input class="input" id="password-reg" type="password" name="pasahitza" required>
            </div>
            <div class="form__group">
                <label class="form__label" for="username">Nombre</label>
                <input class="input" id="username" type="text" name="izena" required>
            </div>
            <button class="btn btn--primary" type="submit">Crear cuenta</button>
            </form>
    </main>

    <!-- Tab switching logic: muestra/oculta formularios al hacer clic en tabs -->
    <script>
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.dataset.target;
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('tab--active'));
                tab.classList.add('tab--active');
                document.querySelectorAll('.form').forEach(form => {
                    form.classList.remove('form--active');
                });
                document.getElementById(target).classList.add('form--active');
            });
        });
    </script>
    <!-- Validaciones del lado del cliente (email, longitud de contraseña, etc.) -->
    <script src="/public/js/validations.js"></script>
</body>
</html>
