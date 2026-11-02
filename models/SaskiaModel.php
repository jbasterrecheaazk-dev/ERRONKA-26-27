<?php
/*
 * MODELS/SASKIAMODEL.PHP
 *
 * Modelo para gestionar la cesta de compra (saskia).
 * Opera sobre la tabla "saskia" de la base de datos.
 * Cada fila representa un producto que un usuario ha añadido a su cesta.
 */

require_once __DIR__ . '/../config/Conexion.php';

class SaskiaModel
{
    private PDO $db;

    public function __construct()
    {
        $conexion = new Conexion();
        $this->db = $conexion->getConexion();
    }

    /*
     * getContador()
     * Devuelve el número de items distintos en la cesta del usuario dado.
     * Cada fila en la tabla saskia cuenta como 1 item.
     */
    public function getContador(int $userId): int
    {
        $sql = "SELECT COUNT(*) as total FROM saskia WHERE erabiltzaile_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    /*
     * getItems()
     * Devuelve todos los productos de la cesta del usuario, con sus datos.
     * Usa JOIN con la tabla "produktuak" para obtener título, precio, etc.
     */
    public function getItems(int $userId): array
    {
        $sql = "
            SELECT
                c.id AS saskia_id,
                c.kantitatea,
                p.id AS produktu_id,
                p.izenburua,
                p.prezioa,
                p.irudia
            FROM saskia c
            INNER JOIN produktuak p ON c.produktu_id = p.id
            WHERE c.erabiltzaile_id = :user_id
            ORDER BY c.sortze_data DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /*
     * addItem()
     * Añade un producto a la cesta del usuario.
     * Si el producto ya existe, incrementa la kantitatea.
     */
    public function addItem(int $userId, int $productId, int $cantidad = 1): void
    {
        // Comprobar si ya existe
        $sqlCheck = "SELECT id, kantitatea FROM saskia WHERE erabiltzaile_id = :uid AND produktu_id = :pid LIMIT 1";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->execute([':uid' => $userId, ':pid' => $productId]);
        $existing = $stmtCheck->fetch();

        if ($existing) {
            // Actualizar cantidad
            $sqlUpdate = "UPDATE saskia SET kantitatea = kantitatea + :cant WHERE id = :id";
            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $stmtUpdate->execute([
                ':cant' => $cantidad,
                ':id'   => $existing['id'],
            ]);
        } else {
            // Insertar nuevo
            $sqlInsert = "INSERT INTO saskia (erabiltzaile_id, produktu_id, kantitatea) VALUES (:uid, :pid, :cant)";
            $stmtInsert = $this->db->prepare($sqlInsert);
            $stmtInsert->execute([
                ':uid' => $userId,
                ':pid' => $productId,
                ':cant' => $cantidad,
            ]);
        }
    }

    /*
     * removeItem()
     * Elimina un item de la cesta por su ID.
     */
    public function removeItem(int $saskiaId): void
    {
        $sql = "DELETE FROM saskia WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $saskiaId]);
    }

    /*
     * actualizarKantitatea()
     * Actualiza la cantidad de un item existente de la cesta.
     */
    public function actualizarKantitatea(int $saskiaId, int $cantidad): void
    {
        $sql = "UPDATE saskia SET kantitatea = :cant WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cant' => max(1, $cantidad),
            ':id'   => $saskiaId,
        ]);
    }

    /*
     * clearUserCart()
     * Vacía completamente la cesta de un usuario.
     */
    public function clearUserCart(int $userId): void
    {
        $sql = "DELETE FROM saskia WHERE erabiltzaile_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
    }

    /*
     * getDb()
     * Expone la conexión PDO para consultas ad-hoc del controlador.
     */
    public function getDb(): PDO
    {
        return $this->db;
    }
}
