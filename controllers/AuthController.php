<?php
/*
 * CONTROLLERS/AUTHCONTROLLER.PHP
 *
 * En el patrón MVC, el Controlador es el intermediario.
 * Recibe las peticiones del usuario, pide datos al Modelo y decide qué mostrar.
 *
 * Imagina un restaurante:
 * - El usuario es el cliente
 * - El Controlador es el camarero
 * - El Modelo es la cocina que prepara la comida
 *
 * Aquí gestionamos el registro, login y logout de usuarios.
 */

// session_start() inicia o recupera la sesión del usuario
// $_SESSION es una variable superglobal que guarda datos entre páginas
// Es como una mochila invisible que el servidor lleva para cada visitante
session_start();

require_once __DIR__ . '/../models/ErabiltzaileaModel.php';

class AuthController
{
    // El controlador necesita un modelo para acceder a los datos
    private ErabiltzaileaModel $model;

    /*
     * Constructor: al crear el controlador, también creamos el modelo
     * Así el controlador puede usar los métodos del modelo sin repetir código
     */
    public function __construct()
    {
        $this->model = new ErabiltzaileaModel();
    }

    /*
     * registro()
     * Gestiona el proceso de crear una cuenta nueva
     *
     * Flujo de datos:
     * 1. Recibe datos del formulario vía $_POST
     * 2. Valida que no estén vacíos
     * 3. Encripta la contraseña con password_hash()
     * 4. Llama al modelo para guardar en la base de datos
     * 5. Redirige al usuario según el resultado
     */
    public function registro(): void
    {
        // Solo aceptamos peticiones POST (formularios), no visitas directas
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../views/pages/login.php');
            exit;
        }

        // Recogemos los datos del formulario
        // ?? '' significa "si no existe, usa cadena vacía"
        $izena = trim($_POST['izena'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pasahitza = $_POST['pasahitza'] ?? '';

        // Validación básica: todos los campos son obligatorios
        if (!$izena || !$email || !$pasahitza) {
            header('Location: ../views/pages/login.php?error=datos_incompletos');
            exit;
        }

        /*
         * password_hash()
         * Encripta la contraseña de forma segura usando bcrypt por defecto
         * Nunca guardamos la contraseña en texto plano
         * Cada vez que encriptas, el resultado es diferente aunque la contraseña sea igual
         * Así nadie puede robar las contraseñas aunque entren en la base de datos
         */
        $hash = password_hash($pasahitza, PASSWORD_DEFAULT);

        try {
            // Llamamos al modelo para que guarde el usuario en la base de datos
            $this->model->registrar([
                'izena'     => $izena,
                'email'     => $email,
                'pasahitza' => $hash,
                'rol'       => 'erabiltzailea'
            ]);
            // Si todo va bien, redirigimos con mensaje de éxito
            header('Location: ../views/pages/login.php?success=registrado');
        } catch (PDOException $e) {
            // Si hay error de base de datos, redirigimos con mensaje de error
            header('Location: ../views/pages/login.php?error=registro_fallido');
        }
        exit;
    }

    /*
     * login()
     * Gestiona el inicio de sesión
     *
     * Flujo de datos:
     * 1. Recibe email y contraseña del formulario vía $_POST
     * 2. Pregunta al modelo si existe un usuario con ese email
     * 3. Compara la contraseña con password_verify()
     * 4. Si es correcta, guarda datos en $_SESSION
     * 5. Redirige según el resultado
     */
    public function login(): void
    {
        // Solo aceptamos peticiones POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../views/pages/login.php');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $pasahitza = $_POST['pasahitza'] ?? '';

        // Preguntamos al modelo si existe un usuario con ese email
        $usuario = $this->model->buscarPorEmail($email);

        /*
         * password_verify()
         * Compara la contraseña que escribió el usuario con el hash guardado en la base de datos
         * Devuelve true si coinciden, false si no
         * Es la forma segura de comprobar contraseñas sin desencriptarlas
         */
        if ($usuario && password_verify($pasahitza, $usuario['pasahitza'])) {
            /*
             * $_SESSION guarda datos del usuario logueado
             * Estos datos persisten mientras dure la sesión (hasta cerrar navegador o logout)
             * Así sabemos quién es el usuario en cada página
             */
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['izena'];
            $_SESSION['usuario_rol'] = $usuario['rol'];

            // Login correcto, vamos a la página principal
            header('Location: ../views/pages/index.php');
        } else {
            // Credenciales incorrectas
            header('Location: ../views/pages/login.php?error=credenciales_invalidas');
        }
        exit;
    }

    /*
     * logout()
     * Cierra la sesión del usuario
     *
     * session_unset() borra todas las variables de sesión
     * session_destroy() destruye la sesión completamente
     */
    public function logout(): void
    {
        session_unset();
        session_destroy();
        header('Location: ../views/pages/index.php');
        exit;
    }
}

/*
 * ROUTING SIMPLE POR ?action=
 *
 * Este bloque actúa como un mini-router para las acciones de autenticación
 * Cuando la URL contiene ?action=login, ?action=registro o ?action=logout,
 * ejecutamos el método correspondiente del controlador
 *
 * Ejemplo:
 * /controllers/AuthController.php?action=login
 * Ejecuta el método login()
 */
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
