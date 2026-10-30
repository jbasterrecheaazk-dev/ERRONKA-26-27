<?php
/*
 * CONFIG/CONEXION.PHP
 *
 * Este archivo gestiona la conexión a la base de datos MySQL.
 * Usa PDO, que es una extensión de PHP para hablar con bases de datos de forma segura.
 *
 * ¿Qué es PDO?
 * PDO (PHP Data Objects) es como un traductor universal entre PHP y la base de datos.
 * Permite conectarse a MySQL, PostgreSQL, SQLite... con el mismo código.
 * Es la forma recomendada de conectarse a bases de datos en PHP.
 */

class Conexion
{
    // Datos de conexión a la base de datos
    // En un proyecto real, esto suele estar en variables de entorno
    private string $host = 'localhost';  // Servidor donde está MySQL
    private string $db   = 'SoinuTruk';  // Nombre de la base de datos
    private string $user = 'root';       // Usuario de MySQL
    private string $pass = '';           // Contraseña de MySQL
    private static ?PDO $pdo = null;            // Aquí guardaremos la conexión de forma estática (Singleton)

    /*
     * getConexion() crea y devuelve la conexión a la base de datos
     *
     * Este método usa el patrón Singleton: si la conexión ya existe,
     * la devuelve tal cual en lugar de crear otra. Así ahorramos recursos
     * y garantizamos que las transacciones funcionen en toda la aplicación.
     */
    public function getConexion(): PDO
    {
        // Si ya hay una conexión activa, la reutilizamos
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        // DSN = Data Source Name. Es la cadena que describe cómo conectarse
        // mysql:host=...;dbname=...;charset=utf8mb4
        $dsn = "mysql:host={$this->host};dbname={$this->db};charset=utf8mb4";

        // Opciones de PDO para que funcione de forma segura y predecible
        $options = [
            // Si ocurre un error, lanza una excepción en lugar de un warning silencioso
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Por defecto devuelve resultados como arrays asociativos (['columna' => valor])
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Desactiva la emulación de prepared statements para usar la nativa de MySQL
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        // Crea la conexión real a la base de datos
        self::$pdo = new PDO($dsn, $this->user, $this->pass, $options);
        return self::$pdo;
    }
}
