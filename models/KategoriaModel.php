<?php
/*
 * MODELS/KATEGORIAMODEL.PHP
 *
 * Modelo para gestionar las categorías de artículos.
 * Opera sobre la tabla "kategoriak" de la base de datos.
 */

require_once __DIR__ . '/../config/Conexion.php';

class KategoriaModel
{
    private PDO $db;

    public function __construct()
    {
        $conexion = new Conexion();
        $this->db = $conexion->getConexion();
    }

    /*
     * getAll()
     * Devuelve todas las categorías de la tabla kategoriak
     */
    public function getAll(): array
    {
        $sql = "SELECT * FROM kategoriak ORDER BY izena ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}
