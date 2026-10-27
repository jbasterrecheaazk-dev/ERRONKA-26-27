<?php
session_start();
require_once __DIR__ . '/../models/ErabiltzaileaModel.php';

class AuthController
{
    private ErabiltzaileaModel $model;

    public function __construct()
    {
        $this->model = new ErabiltzaileaModel();
    }

    public function registro(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../views/pages/login.php');
            exit;
        }

        $izena = trim($_POST['izena'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pasahitza = $_POST['pasahitza'] ?? '';

        if (!$izena || !$email || !$pasahitza) {
            header('Location: ../views/pages/login.php?error=datos_incompletos');
            exit;
        }

        $hash = password_hash($pasahitza, PASSWORD_DEFAULT);

        try {
            $this->model->registrar([
                'izena'     => $izena,
                'email'     => $email,
                'pasahitza' => $hash,
                'rol'       => 'erabiltzailea'
            ]);
            header('Location: ../views/pages/login.php?success=registrado');
        } catch (PDOException $e) {
            header('Location: ../views/pages/login.php?error=registro_fallido');
        }
        exit;
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../views/pages/login.php');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $pasahitza = $_POST['pasahitza'] ?? '';

        $usuario = $this->model->buscarPorEmail($email);

        if ($usuario && password_verify($pasahitza, $usuario['pasahitza'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['izena'];
            $_SESSION['usuario_rol'] = $usuario['rol'];
            header('Location: ../views/pages/index.php');
        } else {
            header('Location: ../views/pages/login.php?error=credenciales_invalidas');
        }
        exit;
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();
        header('Location: ../views/pages/index.php');
        exit;
    }
}

// Routing simple por ?action=
if (isset($_GET['action'])) {
    $controller = new AuthController();
    switch ($_GET['action']) {
        case 'registro':
            $controller->registro();
            break;
        case 'login':
            $controller->login();
            break;
        case 'logout':
            $controller->logout();
            break;
    }
}
