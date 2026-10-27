<?php
/*
 * MODELS/CARRITOMODEL.PHP
 *
 * Modelo para gestionar el carrito de compras.
 * Opera sobre la tabla "carrito" de la base de datos.
 * Cada fila representa un producto que un usuario ha puesto en su carrito.
 */

require_once __DIR__ . '/../config/Conexion.php';

class CarritoModel
{
    private PDO $db;

    public function __construct()
    {
        $conexion = new Conexion();
        $this->db = $conexion->getConexion();
    }

    /*
     * getContador()
     * Devuelve el número de items distintos en el carrito del usuario dado.
     * Cada row en la tabla carrito cuenta como 1 item (independientemente de kantitatea).
     */
    public function getContador(int $userId): int
    {
        $sql = "SELECT COUNT(*) as total FROM carrito WHERE erabiltzaile_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    /*
     * getItems()
     * Devuelve todos los productos del carrito del usuario, con sus datos.
     * Usa JOIN con la tabla "produktuak" para obtener título, precio, etc.
     */
    public function getItems(int $userId): array
    {
        $sql = "
            SELECT
                c.id AS carrito_id,
                c.kantitatea,
                p.id AS produktu_id,
                p.izenburua,
                p.prezioa,
                p.egoera,
                p.irudia
            FROM carrito c
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
     * Añade un producto al carrito del usuario.
     * Si el producto ya existe en el carrito, incrementa kantitatea.
     */
    public function addItem(int $userId, int $productId, int $cantidad = 1): void
    {
        // Comprobar si ya existe
        $sqlCheck = "SELECT id, kantitatea FROM carrito WHERE erabiltzaile_id = :uid AND produktu_id = :pid LIMIT 1";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->execute([':uid' => $userId, ':pid' => $productId]);
        $existing = $stmtCheck->fetch();

        if ($existing) {
            // Actualizar cantidad
            $sqlUpdate = "UPDATE carrito SET kantitatea = kantitatea + :cant WHERE id = :id";
            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $stmtUpdate->execute([
                ':cant' => $cantidad,
                ':id'   => $existing['id'],
            ]);
        } else {
            // Insertar nuevo
            $sqlInsert = "INSERT INTO carrito (erabiltzaile_id, produktu_id, kantitatea) VALUES (:uid, :pid, :cant)";
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
     * Elimina un item del carrito por su ID de carrito.
     */
    public function removeItem(int $cartItemId): void
    {
        $sql = "DELETE FROM carrito WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $cartItemId]);
    }

    /*
     * clearUserCart()
     * Vacía completamente el carrito de un usuario.
     */
    public function clearUserCart(int $userId): void
    {
        $sql = "DELETE FROM carrito WHERE erabiltzaile_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
    }
}
