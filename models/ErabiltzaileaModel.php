<?php
/**
 * MODELS/ERABILTZAILEAMODEL.PHP
 *
 * Modelo para gestionar la entidad "Usuario" (erabiltzailea = usuario en euskera).
 * Opera sobre la tabla "erabiltzaileak" de la base de datos.
 *
 * Responsabilidades:
 * - Registrar nuevos usuarios (INSERT con password_hash).
 * - Buscar usuarios por email (para login).
 * - Buscar usuarios por ID (para obtener datos del perfil).
 *
 * Seguridad: Todas las consultas usan sentencias preparadas PDO
 * con parámetros nombrados (:param) para prevenir inyección SQL.
 */

require_once __DIR__ . '/../config/Conexion.php';

/**
 * Clase que encapsula el acceso a datos de la entidad "Usuario".
 * Implementa el patrón DAO (Data Access Object) del MVC.
 */
class ErabiltzaileaModel
{
    /** @var PDO Conexión a la base de datos (Singleton vía Conexion) */
    private PDO $db;

    /**
     * Constructor: obtiene la conexión a la BD una sola vez.
     * El patrón Singleton en Conexion asegura que todas las models
     * compartan la misma conexión, esencial para transacciones.
     */
    public function __construct()
    {
        $conexion = new Conexion();
        $this->db = $conexion->getConexion();
    }

    /**
     * Registrar un nuevo usuario en la base de datos.
     *
     * La contraseña se encripta con bcrypt antes de almacenarse.
     * NUNCA se guarda texto plano por seguridad.
     *
     * @param array $datos Array con keys: 'izena', 'email', 'pasahitza', 'rol' (opcional)
     * @return bool True si la inserción fue exitosa, false en caso contrario
     */
    public function registrar(array $datos): bool
    {
        // SQL preparado con marcadores nombrados para prevenir inyección SQL
        $sql = "INSERT INTO erabiltzaileak (izena, email, pasahitza, rol) VALUES (:izena, :email, :pasahitza, :rol)";

        // prepare() convierte el SQL en una plantilla segura
        $stmt = $this->db->prepare($sql);

        // execute() sustituye los marcadores por valores reales de forma segura
        return $stmt->execute([
            ':izena'     => $datos['izena'],
            ':email'     => $datos['email'],
            ':pasahitza' => $datos['pasahitza'],
            // Si no se especifica rol, se asigna 'erabiltzailea' (usuario normal)
            ':rol'       => $datos['rol'] ?? 'erabiltzailea',
        ]);
    }

    /**
     * Buscar un usuario por su dirección de correo electrónico.
     * Usado en el login para verificar credenciales.
     *
     * @param string $email Dirección de email a buscar
     * @return array|null Array con datos del usuario o null si no existe
     */
    public function buscarPorEmail(string $email): ?array
    {
        // LIMIT 1 optimiza la consulta sabiendo que el email es único
        $sql = "SELECT * FROM erabiltzaileak WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);

        // fetch() obtiene la primera fila como array asociativo
        $usuario = $stmt->fetch();
        return $usuario ?: null;
    }

    /**
     * Buscar un usuario por su ID numérico.
     * Usado para obtener datos completos del perfil (incluyendo fecha de registro).
     *
     * @param int $id Identificador numérico del usuario
     * @return array|null Array con datos del usuario o null si no existe
     */
    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT * FROM erabiltzaileak WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $usuario = $stmt->fetch();
        return $usuario ?: null;
    }
}
