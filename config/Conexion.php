<?php
/**
 * CONFIG/CONEXION.PHP
 *
 * Clase singleton que gestiona la conexión a la base de datos MySQL
 * mediante PDO (PHP Data Objects).
 *
 * Patrón Singleton: asegura que solo exista UNA conexión a la BD
 * durante toda la ejecución del script, lo cual es esencial para:
 * - Transacciones PDO (beginTransaction/commit/rollBack)
 * - Evitar múltiples conexiones innecesarias al servidor MySQL
 *
 * Configuración de PDO:
 * - ERRMODE_EXCEPTION: lanza excepciones en vez de silenciar errores
 * - FETCH_ASSOC: devuelve resultados como array asociativo
 * - EMULATE_PREPARES=false: usa sentencias preparadas reales del driver MySQL
 *
 * Los valores de conexión se leen desde variables de entorno ($_ENV)
 * con valores por defecto para desarrollo local.
 */

/**
 * Clase que gestiona la conexión a la base de datos.
 * Implementa el patrón Singleton para una sola conexión PDO.
 */
class Conexion
{
    /** @var PDO|null Instancia única de conexión PDO */
    private static ?PDO $instancia = null;

    /**
     * Constructor privado: previene la instanciación directa.
     * Usa valores de entorno con fallback a localhost.
     */
    private function __construct()
    {
        // Leer credenciales desde variables de entorno o usar valores por defecto
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $dbname = $_ENV['DB_NAME'] ?? 'soinutruk_db';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? '';

        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

        try {
            // Crear conexión PDO con opciones de seguridad
            self::$instancia = new PDO($dsn, $user, $pass, [
                // ERRMODE_EXCEPTION: las errores de SQL lanzan excepciones PDOException
                // Esto permite捕获 errores y manejarlos con try/catch
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

                // FETCH_ASSOC: los resultados se devuelven como array asociativo
                // Las columnas se acceden por nombre en lugar de índice numérico
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

                // EMULATE_PREPARES=false: usa sentencias preparadas nativas de MySQL
                // Esto es más seguro porque el driver envía los parámetros separadamente
                // de la consulta SQL, evitando incluso intentos de inyección
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            // En producción, loggear el error en lugar de mostrarlo
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene la instancia singleton de la conexión PDO.
     * Si no existe, la crea (first call). Si ya existe, la retorna.
     *
     * @return PDO Instancia de conexión a la base de datos
     */
    public static function getConexion(): PDO
    {
        if (self::$instancia === null) {
            new self();
        }
        return self::$instancia;
    }

    /**
     * Previenen clonación del objeto singleton.
     */
    private function __clone() {}

    /**
     * Previenen deserialización del objeto singleton.
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
