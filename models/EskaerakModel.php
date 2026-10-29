<?php
require_once __DIR__ . '/../config/Conexion.php';

class EskaerakModel
{
    private PDO $db;

    public function __construct()
    {
        $conexion = new Conexion();
        $this->db = $conexion->getConexion();
    }

    /**
     * Crea un pedido (osatua) por cada producto del carrito.
     * Marca cada producto como no disponible (salgai = 0).
     * Vacía el carrito del usuario.
     *
     * @param int   $erosleId      ID del comprador (usuario logueado)
     * @param array $productoIds   Lista de IDs de productos en el carrito
     * @param float $totalGlobal   Total de la compra (se guarda en cada eskaera)
     * @return bool
     */
    public function crear(int $erosleId, array $productoIds, float $totalGlobal): bool
    {
        try {
            $this->db->beginTransaction();

            foreach ($productoIds as $pId) {
                // Precio real del producto (por si hubo cambio)
                $sqlPrecio = "SELECT prezioa FROM produktuak WHERE id = :pid LIMIT 1";
                $stmtP = $this->db->prepare($sqlPrecio);
                $stmtP->execute([':pid' => $pId]);
                $precio = (float) $stmtP->fetchColumn();

                // Insertar registro de pedido (uno por producto, egoera = 'osatua')
                $sql = "INSERT INTO eskaerak (erosle_id, produktu_id, totala, egoera)
                        VALUES (:erosle_id, :produktu_id, :totala, 'osatua')";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':erosle_id'   => $erosleId,
                    ':produktu_id' => $pId,
                    ':totala'      => $precio,
                ]);

                // Marcar producto como vendido
                $sqlProd = "UPDATE produktuak SET salgai = 0 WHERE id = :pid";
                $stmtProd = $this->db->prepare($sqlProd);
                $stmtProd->execute([':pid' => $pId]);
            }

            // Vaciar carrito del usuario
            $sqlCart = "DELETE FROM saskia WHERE erabiltzaile_id = :uid";
            $stmtCart = $this->db->prepare($sqlCart);
            $stmtCart->execute([':uid' => $erosleId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /*
     * lortuEstatistikoak()
     * Calcula métricas agregadas para el dashboard de administración:
     * - total_ingresos: SUM(totala) de pedidos completados (egoera = 'osatua')
     * - total_pedidos: COUNT de pedidos completados
     * - total_usuarios: COUNT de usuarios registrados
     * - productos_activos: COUNT de productos con salgai = 1 y onarpen_egoera = 'onartua'
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

    /*
     * lortuAzkenEskaerak()
     * Obtiene los últimos pedidos realizados para mostrar actividad reciente.
     * Incluye datos del producto y del comprador.
     *
     * @param int $muga Límite de resultados (por defecto 5)
     * @return array
     */
    public function lortuAzkenEskaerak(int $muga = 5): array
    {
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

    /*
     * lortuEroslearenEskaerak()
     * Obtiene todos los pedidos de un usuario específico (historial de compras).
     *
     * @param int $erosleId ID del usuario comprador
     * @return array
     */
    public function lortuEroslearenEskaerak(int $erosleId): array
    {
        $sql = "SELECT e.*, p.izenburua as producto_izenburua, u.izena as saltzaile_nombre,
                    p.artista, p.urtea, p.prezioa as producto_prezioa
               FROM eskaerak e
               JOIN produktuak p ON e.produktu_id = p.id
               JOIN erabiltzaileak u ON p.saltzaile_id = u.id
               WHERE e.erosle_id = :erosle_id AND e.egoera = 'osatua'
               ORDER BY e.data DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':erosle_id' => $erosleId]);
        return $stmt->fetchAll() ?: [];
    }
}