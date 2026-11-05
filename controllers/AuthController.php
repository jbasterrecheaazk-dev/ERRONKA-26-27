<?php
/**
 * CONTROLLERS/AUTHCONTROLLER.PHP
 *
 * Controlador de autenticación: registro, login y logout de usuarios.
 * Opera como punto final para los formularios de la vista login.php.
 *
 * Notas importantes:
 * - Contiene su propia llamada a session_start() en línea 19 (considerada redundante
 *   ya que index.php también la invoca; se mantiene por compatibilidad).
 * - Usa el patrón "mini-router" con query string (?action=) para enrutar peticiones.
 * - Todas las respuestas terminan con exit para evitar procesamiento adicional.
 */

// session_start() inicia o recupera la sesión del usuario
// $_SESSION es una variable superglobal que guarda datos entre páginas
// Es como una mochila invisible que el servidor lleva para cada visitante
session_start();

require_once __DIR__ . '/../models/ErabiltzaileaModel.php';

/**
 * Clase que maneja todas las operaciones de autenticación del sistema.
 * Utiliza el patrón Controller del MVC para orquestar el modelo de usuarios.
 */
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

    /**
     * Gestiona el proceso de registro de un nuevo usuario.
     *
     * Flujo:
     * 1. Verifica que la petición sea POST
     * 2. Recoge y trimsea los campos del formulario (izena, email, pasahitza)
     * 3. Valida que ningún campo esté vacío
     * 4. Encripta la contraseña con password_hash() (bcrypt)
     * 5. Llama al modelo para insertar en la BD
     * 6. Redirige a /login con parámetro de éxito o error
     *
     * @return void Nunca retorna (siempre redirige o termina el script)
     */
    public function registro(): void
    {
        // Solo aceptamos peticiones POST (formularios), no visitas directas
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }

        // Recogemos los datos del formulario
        // ?? '' significa "si no existe, usa cadena vacía"
        $izena = trim($_POST['izena'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pasahitza = $_POST['pasahitza'] ?? '';

        // Validación básica: todos los campos son obligatorios
        if (!$izena || !$email || !$pasahitza) {
            header('Location: /login?error=datos_incompletos');
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
            header('Location: /login?success=registrado');
        } catch (PDOException $e) {
            // Si hay error de base de datos, redirigimos con mensaje de error
            header('Location: /login?error=registro_fallido');
        }
        exit;
    }

    /**
     * Gestiona el inicio de sesión de un usuario existente.
     *
     * Flujo:
     * 1. Verifica que la petición sea POST
     * 2. Busca el usuario por email usando el modelo
     * 3. Compara la contraseña con password_verify()
     * 4. Si coincide, almacena datos del usuario en $_SESSION
     * 5. Redirige a la página principal (/) o muestra error
     *
     * Datos almacenados en sesión:
     *   - usuario_id: ID numérico del usuario
     *   - usuario_nombre: nombre display (izena)
     *   - usuario_email: dirección de correo
     *   - usuario_rol: 'admin' o 'erabiltzailea'
     *
     * @return void Nunca retorna (siempre redirige)
     */
    public function login(): void
    {
        // Solo aceptamos peticiones POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
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
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['usuario_rol'] = $usuario['rol'];

            // Login correcto, vamos a la página principal
            header('Location: /');
        } else {
            // Credenciales incorrectas
            header('Location: /login?error=credenciales_invalidas');
        }
        exit;
    }

    /**
     * Cierra la sesión del usuario actualmente logueado.
     *
     * Flujo:
     * 1. session_unset() elimina todas las variables de sesión
     * 2. session_destroy() cierra la sesión en el servidor
     * 3. Redirige a la página principal
     *
     * @return void
     */
    public function logout(): void
    {
        session_unset();
        session_destroy();
        header('Location: /');
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
