<?php
/**
 * MODELS/ESKERAKMODEL.PHP
 *
 * Modelo para gestionar los pedidos (eskaerak = pedidos en euskera).
 * Opera sobre la tabla "eskaerak" de la base de datos.
 *
 * Cada pedido tiene un estado (egoera):
 *   - 'osatua' = completado (compra confirmada)
 *
 * Nota sobre transacciones: Este modelo NO inicia transacciones propias.
 * Las transacciones (beginTransaction/commit/rollBack) se gestionan desde
 * EskaerakController para evitar bloqueos concurrentes con FOR UPDATE.
 */

require_once __DIR__ . '/../config/Conexion.php';

/**
 * Clase que encapsula el acceso a datos de la entidad "Pedido".
 * Implementa el patrón DAO (Data Access Object) del MVC.
 */
class EskaerakModel
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
     * Asigna una conexión PDO externa (por ejemplo, la de una transacción
     * iniciada desde el controlador) para evitar bloqueos por doble conexión.
     */
    public function setDb(PDO $db): void
    {
        $this->db = $db;
    }

    /**
     * Registrar los detalles del pedido y marcar productos como vendidos.
     *
     * ATENCIÓN: Este método NO inicia transacciones propias para evitar
     * bloqueos con el controlador que ya maneja la transacción con FOR UPDATE.
     *
     * Flujo:
     * 1. Por cada producto, obtiene su precio real de la BD
     * 2. Inserta un registro en eskaerak (uno por producto, egoera = 'osatua')
     * 3. Marca el producto como no disponible (salgai = 0)
     * 4. Vacía la cesta del comprador
     *
     * @param int $erosleId ID del comprador (erosle = comprador en euskera)
     * @param int[] $productoIds Lista de IDs de productos en el carrito
     * @param float $totalGlobal Total de la compra (para registro)
     * @return bool True si todas las operaciones fueron exitosas
     * @throws Exception Si ocurre un error en cualquiera de las operaciones
     */
    public function crear(int $erosleId, array $productoIds, float $totalGlobal): bool
    {
        try {
            foreach ($productoIds as $pId) {
                // Obtener precio real del producto (no confiar en el precio del carrito)
                $sqlPrecio = "SELECT prezioa FROM produktuak WHERE id = :pid LIMIT 1";
                $stmtP = $this->db->prepare($sqlPrecio);
                $stmtP->execute([':pid' => $pId]);
                $precio = (float) $stmtP->fetchColumn();

                // Insertar registro de pedido (uno por producto, estado 'osatua' = completado)
                $sql = "INSERT INTO eskaerak (erosle_id, produktu_id, totala, egoera)
                        VALUES (:erosle_id, :produktu_id, :totala, 'osatua')";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':erosle_id'   => $erosleId,
                    ':produktu_id' => $pId,
                    ':totala'      => $precio, // Precio real, no el del carrito
                ]);

                // Marcar producto como vendido (no disponible para otros)
                $sqlProd = "UPDATE produktuak SET salgai = 0 WHERE id = :pid";
                $stmtProd = $this->db->prepare($sqlProd);
                $stmtProd->execute([':pid' => $pId]);
            }

            // Vaciar carrito del usuario tras compra exitosa
            $sqlCart = "DELETE FROM saskia WHERE erabiltzaile_id = :uid";
            $stmtCart = $this->db->prepare($sqlCart);
            $stmtCart->execute([':uid' => $erosleId]);

            return true;
        } catch (Exception $e) {
            // Re-lanzar para que el controlador capture y haga rollback
            throw $e;
        }
    }

    /**
     * Calcular métricas agregadas para el dashboard de administración.
     *
     * Métricas calculadas:
     *   - total_ingresos: SUM(totala) de pedidos completados (EGOERA = 'osatua')
     *   - total_pedidos: COUNT de pedidos completados
     *   - total_usuarios: COUNT de usuarios registrados
     *   - productos_activos: COUNT de productos disponibles (salgai = 1 y onarpen_egoera = 'onartua')
     *
     * @return array Array con las métricas calculadas
     */
    public function lortuEstatistikoak(): array
    {
        // Total ingresos y pedidos completados
        $sqlVentas = "SELECT
                        COALESCE(SUM(totala), 0) as total_ingresos,
                        COUNT(*) as total_pedidos
                      FROM eskaerak
                      WHERE egoera = 'osatua'";
        $stmtV = $this->db->query($sqlVentas);
        $ventas = $stmtV->fetch();

        // Total usuarios registrados
        $sqlUsers = "SELECT COUNT(*) as total_usuarios FROM erabiltzaileak";
        $stmtU = $this->db->query($sqlUsers);
        $users = $stmtU->fetch();

        // Productos activos en catálogo
        $sqlProd = "SELECT COUNT(*) as productos_activos
                    FROM produktuak
                    WHERE salgai = 1 AND onarpen_egoera = 'onartua'";
        $stmtP = $this->db->query($sqlProd);
        $prod = $stmtP->fetch();

        return [
            'total_ingresos'   => (float) ($ventas['total_ingresos'] ?? 0),
            'total_pedidos'    => (int) ($ventas['total_pedidos'] ?? 0),
            'total_usuarios'   => (int) ($users['total_usuarios'] ?? 0),
            'productos_activos' => (int) ($prod['productos_activos'] ?? 0),
        ];
    }

    /**
     * Obtener los últimos pedidos realizados para mostrar actividad reciente.
     * Incluye datos del producto y del comprador para visualización en el admin.
     *
     * @param int $muga Límite de resultados (por defecto 5)
     * @return array Lista de pedidos recientes
     */
    public function lortuAzkenEskaerak(int $muga = 5): array
    {
        // JOIN con produktuak y erabiltzaileak para datos completos
        $sql = "SELECT e.*, p.izenburua as producto_izenburua, u.izena as erosle_nombre, u.email as erosle_email
                FROM eskaerak e
                JOIN produktuak p ON e.produktu_id = p.id
                JOIN erabiltzaileak u ON e.erosle_id = u.id
                WHERE e.egoera = 'osatua'
                ORDER BY e.data DESC
                LIMIT " . (int)$muga;
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Obtener todos los pedidos de un usuario específico (historial de compras).
     * Incluye datos del producto, vendedor y artista.
     *
     * @param int $erosleId ID del usuario comprador
     * @return array Lista de pedidos ordenados por fecha (más reciente primero)
     */
    public function lortuEroslearenEskaerak(int $erosleId): array
    {
        // JOIN con produktuak (datos del producto) y erabiltzaileak (vendedor)
        $sql = "SELECT e.*, p.izenburua as producto_izenburua, u.izena as saltzaile_nombre,
                    p.artista, p.urtea, p.prezioa as producto_prezioa
               FROM eskaerak e
               JOIN produktuak p ON e.produktu_id = p.id
               JOIN erabiltzaileak u ON p.saltzaile_id = u.id
               WHERE e.erosle_id = :erosle_id AND e.egoera = 'osatua'
               ORDER BY e.data ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':erosle_id' => $erosleId]);
        $pedidos = $stmt->fetchAll() ?: [];

        // Asignar un contador relativo por usuario (número de pedido)
        $i = 1;
        foreach ($pedidos as &$pedido) {
            $pedido['ordena_zenbakia'] = $i++;
        }

        // Devolver ordenados descendente para que el último pedido sea el primero visualmente
        return array_reverse($pedidos);
    }
}
