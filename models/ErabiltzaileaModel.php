<?php
/*
 * MODELS/ERABILTZAILEAMODEL.PHP
 *
 * En el patrón MVC, el Modelo es la parte que habla con la base de datos.
 * No muestra nada al usuario, solo guarda y recupera datos.
 *
 * Este modelo gestiona a los usuarios: registrarse y buscar por email.
 *
 * ¿Qué es un Modelo?
 * Es la capa de acceso a datos. Aquí viven las consultas SQL,
 * la lógica de negocio relacionada con los datos y las reglas de validación básicas.
 */

require_once __DIR__ . '/../config/Conexion.php';

class ErabiltzaileaModel
{
    // La propiedad $db guarda la conexión a la base de datos
    // Usamos PDO para seguridad y eficiencia
    private PDO $db;

    /*
     * Constructor: se ejecuta automáticamente cuando creamos un objeto de esta clase
     * Aquí obtenemos la conexión a la base de datos una sola vez
     */
    public function __construct()
    {
        $conexion = new Conexion();
        $this->db = $conexion->getConexion();
    }

    /*
     * registrar()
     * Inserta un nuevo usuario en la tabla erabiltzaileak
     *
     * Recibe un array con los datos del usuario
     * Devuelve true si se insertó correctamente, false si falló
     *
     * IMPORTANTE: Usamos SENTENCIAS PREPARADAS para evitar inyección SQL.
     * ¿Qué es inyección SQL?
     * Es un ataque donde el usuario introduce código SQL malicioso en un formulario.
     * Con sentencias preparadas, los datos se tratan siempre como texto,
     * nunca como código SQL. Es la forma más segura de trabajar con bases de datos.
     */
    public function registrar(array $datos): bool
    {
        // SQL con marcadores :nombre que luego reemplazaremos con datos reales
        $sql = "INSERT INTO erabiltzaileak (izena, email, pasahitza, rol) VALUES (:izena, :email, :pasahitza, :rol)";

        // prepare() convierte el SQL en una plantilla segura
        $stmt = $this->db->prepare($sql);

        // execute() sustituye los marcadores por los valores reales de forma segura
        return $stmt->execute([
            ':izena'     => $datos['izena'],
            ':email'     => $datos['email'],
            ':pasahitza' => $datos['pasahitza'],
            ':rol'       => $datos['rol'] ?? 'erabiltzailea', // Si no se pasa rol, usa 'erabiltzailea' por defecto
        ]);
    }

    /*
     * buscarPorEmail()
     * Busca un usuario en la base de datos por su dirección de correo
     *
     * Recibe un email como texto
     * Devuelve un array con los datos del usuario o null si no existe
     *
     * También usa sentencias preparadas para máxima seguridad
     */
    public function buscarPorEmail(string $email): ?array
    {
        // SQL con marcador :email
        $sql = "SELECT * FROM erabiltzaileak WHERE email = :email LIMIT 1";

        // Creamos la sentencia preparada
        $stmt = $this->db->prepare($sql);

        // Ejecutamos con el valor real del email
        $stmt->execute([':email' => $email]);

        // fetch() obtiene la primera fila del resultado como array asociativo
        $usuario = $stmt->fetch();

        // Si no encontramos usuario, devolvemos null
        return $usuario ?: null;
    }
}
