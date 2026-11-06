<?php
/**
 * MODELS/KATEGORIAMODEL.PHP
 *
 * Modelo para gestionar las categorías de artículos del marketplace.
 * Opera sobre la tabla "kategoriak" de la base de datos.
 *
 * Responsabilidades:
 * - Recuperar todas las categorías para los selectores del frontend.
 *
 * Nota: Las tablas usan nombres en euskera (idioma vasco):
 *   kategoriak = categorías
 *   izena = nombre
 */

require_once __DIR__ . '/../config/Conexion.php';

/**
 * Clase que encapsula el acceso a datos de la entidad "Categoría".
 * Implementa el patrón DAO (Data Access Object) del MVC.
 */
class KategoriaModel
{
    /** @var PDO Conexión a la base de datos compartida (Singleton vía Conexion) */
    private PDO $db;

    /**
     * Constructor: obtiene la conexión a la BD una sola vez.
     * Utiliza el patrón Singleton de Conexion para evitar múltiples conexiones.
     */
    public function __construct()
    {
        $conexion = new Conexion();
        $this->db = $conexion->getConexion();
    }

    /**
     * Obtener todas las categorías ordenadas alfabéticamente.
     *
     * Esta consulta es simple (SELECT * sin WHERE) porque las categorías
     * son datos estáticos de catálogo, no dinámicos por usuario.
     *
     * @return array Lista de categorías con sus campos (id, izena, etc.)
     */
    public function getAll(): array
    {
        // Ordenar alfabéticamente por nombre (izena) para mostrar en selects
        $sql = "SELECT * FROM kategoriak ORDER BY izena ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}
