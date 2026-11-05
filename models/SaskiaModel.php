<?php
/**
 * MODELS/SASKIAMODEL.PHP
 *
 * Modelo para gestionar la cesta de compra (saskia = cesta en euskera).
 * Opera sobre la tabla "saskia" de la base de datos.
 * Cada fila representa un producto que un usuario ha añadido a su cesta.
 *
 * Nota: Este marketplace es C2C (consumidor a consumidor) de música física,
 * por lo que cada producto es único y la cantidad (kantitatea) se usa
 * principalmente para contabilidad interna, no para stock múltiple.
 */

require_once __DIR__ . '/../config/Conexion.php';

/**
 * Clase que encapsula el acceso a datos de la entidad "Cesta de compra".
 * Implementa el patrón DAO (Data Access Object) del MVC.
 */
class SaskiaModel
{
    /** @var PDO Conexión a la base de datos (Singleton vía Conexion) */
    private PDO $db;

    /**
     * Constructor: obtiene la conexión a la BD una sola vez.
     */
    public function __construct()
    {
        $conexion = new Conexion();
        $this->db = $conexion->getConexion();
    }

    /**
     * Contar el número de items distintos en la cesta del usuario.
     * Cada fila en la tabla saskia cuenta como 1 item (productos únicos).
     *
     * @param int $userId ID del usuario propietario de la cesta
     * @return int Número de productos en la cesta
     */
    public function getContador(int $userId): int
    {
        $sql = "SELECT COUNT(*) as total FROM saskia WHERE erabiltzaile_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    /**
     * Obtener todos los productos de la cesta del usuario con sus detalles.
     * Usa INNER JOIN con la tabla "produktuak" para obtener título, precio e imagen.
     *
     * @param int $userId ID del usuario propietario de la cesta
     * @return array Lista de items con datos del producto
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

    /**
     * Añadir un producto a la cesta del usuario.
     * Si el producto ya existe en la cesta, incrementa la cantidad (kantitatea).
     *
     * @param int $userId ID del usuario
     * @param int $productId ID del producto a añadir
     * @param int $cantidad Cantidad a añadir (por defecto 1)
     */
    public function addItem(int $userId, int $productId, int $cantidad = 1): void
    {
        // Comprobar si el producto ya está en la cesta
        $sqlCheck = "SELECT id, kantitatea FROM saskia WHERE erabiltzaile_id = :uid AND produktu_id = :pid LIMIT 1";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->execute([':uid' => $userId, ':pid' => $productId]);
        $existing = $stmtCheck->fetch();

        if ($existing) {
            // Actualizar cantidad existente
            $sqlUpdate = "UPDATE saskia SET kantitatea = kantitatea + :cant WHERE id = :id";
            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $stmtUpdate->execute([
                ':cant' => $cantidad,
                ':id'   => $existing['id'],
            ]);
        } else {
            // Insertar nuevo item en la cesta
            $sqlInsert = "INSERT INTO saskia (erabiltzaile_id, produktu_id, kantitatea) VALUES (:uid, :pid, :cant)";
            $stmtInsert = $this->db->prepare($sqlInsert);
            $stmtInsert->execute([
                ':uid' => $userId,
                ':pid' => $productId,
                ':cant' => $cantidad,
            ]);
        }
    }

    /**
     * Eliminar un item de la cesta por su ID interno.
     *
     * @param int $saskiaId ID del registro en la tabla saskia
     */
    public function removeItem(int $saskiaId): void
    {
        $sql = "DELETE FROM saskia WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $saskiaId]);
    }

    /**
     * Actualizar la cantidad de un item existente en la cesta.
     * La cantidad mínima es 1 (no se permite 0 o negativos).
     *
     * @param int $saskiaId ID del registro en la tabla saskia
     * @param int $cantidad Nueva cantidad
     */
    public function actualizarKantitatea(int $saskiaId, int $cantidad): void
    {
        $sql = "UPDATE saskia SET kantitatea = :cant WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cant' => max(1, $cantidad), // Mínimo 1
            ':id'   => $saskiaId,
        ]);
    }

    /**
     * Vaciar completamente la cesta de un usuario.
     * Usado tras completar una compra exitosa.
     *
     * @param int $userId ID del usuario
     */
    public function clearUserCart(int $userId): void
    {
        $sql = "DELETE FROM saskia WHERE erabiltzaile_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
    }

    /**
     * Exponer la conexión PDO para consultas ad-hoc del controlador.
     * Usado por SaskiaController para verificar duplicados antes de añadir.
     *
     * @return PDO Instancia de conexión
     */
    public function getDb(): PDO
    {
        return $this->db;
    }
}
