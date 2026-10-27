<?php
/*
 * MODELS/PRODUCTOMODEL.PHP
 *
 * Modelo para gestionar los productos/artículos a la venta.
 * Opera sobre la tabla "produktuak" de la base de datos.
 */

require_once __DIR__ . '/../config/Conexion.php';

class ProductoModel
{
    private PDO $db;

    public function __construct()
    {
        $conexion = new Conexion();
        $this->db = $conexion->getConexion();
    }

    /*
     * crear()
     * Inserta un nuevo producto en la tabla produktuak.
     * Usa sentencias preparadas para evitar inyección SQL.
     *
     * $datos debe contener:
     *   - izenburua: nombre/título del producto
     *   - deskribapena: descripción
     *   - prezioa: precio
     *   - kategoria_id: ID de la categoría
     *   - saltzaile_id: ID del vendedor (usuario logueado)
     *   - artista: nombre del artista/banda (opcional)
     *   - urtea: año de publicación (opcional)
     *   - egoera: estado de conservación (opcional)
     *   - irudia: ruta de la imagen (opcional)
     */
    public function crear(array $datos): bool
    {
        $sql = "INSERT INTO produkt
                (izenburua, deskribapena, prezioa, kategoria_id, saltzaile_id,
                 artista, urtea, egoera, irudia)
                VALUES (
                    :izenburua, :deskribapena, :prezioa, :kategoria_id, :saltzaile_id,
                    :artista, :urtea, :egoera, :irudia
                )";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':izenburua'    => $datos['izenburua'],
            ':deskribapena' => $datos['deskribapena'],
            ':prezioa'      => $datos['prezioa'],
            ':kategoria_id' => $datos['kategoria_id'] ?: null,
            ':saltzaile_id' => $datos['saltzaile_id'],
            ':artista'      => $datos['artista'] ?? null,
            ':urtea'        => $datos['urtea'] ?? null,
            ':egoera'       => $datos['egoera'] ?? null,
            ':irudia'       => $datos['irudia'] ?? null,
        ]);
    }
}
