<?php
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
        <?php if ($success === 'registrado'): ?>
            <p>Registro completado. Ahora puedes iniciar sesión.</p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p>Error: <?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <div class="login__tabs">
            <button class="tab tab--active" data-target="login">Iniciar sesión</button>
            <button class="tab" data-target="register">Registrarse</button>
        </div>

        <!-- Formulario Login -->
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
            <div class="form__footer">
                ¿Olvidaste tu contraseña? <a class="link" href="#">Recuperar</a>
            </div>
        </form>

        <!-- Formulario Registro -->
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
            <div class="form__footer">
                Al registrarte aceptas nuestros <a class="link" href="#">Términos</a>
            </div>
        </form>
    </main>

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
    <script src="/public/js/validations.js"></script>
</body>
</html>