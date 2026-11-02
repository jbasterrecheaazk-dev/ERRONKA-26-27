<?php
/*
 * CONTROLLERS/ESKERARESKONTROLLER.PHP
 *
 * Controlador REST para procesar pedidos de compra.
 */

require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../models/SaskiaModel.php';
require_once __DIR__ . '/../models/EskaerakModel.php';

class EskaerakController
{
    private SaskiaModel $saskiaModel;
    private EskaerakModel $eskaerakModel;

    public function __construct()
    {
        $this->saskiaModel = new SaskiaModel();
        $this->eskaerakModel = new EskaerakModel();
    }

    private function json(array $data, int $status = 200): void
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    private function requireAuth(): int
    {
        if (!isset($_SESSION['usuario_id'])) {
            $this->json(['error' => 'Unauthenticated'], 401);
        }
        return (int)$_SESSION['usuario_id'];
    }

    public function procesar(): void
    {
        $userId = $this->requireAuth();

        $items = $this->saskiaModel->getItems($userId);
        if (empty($items)) {
            $this->json(['error' => 'Carrito vacío'], 400);
        }

        $db = (new Conexion())->getConexion();

        try {
            $db->beginTransaction();

            // Verificar disponibilidad y estado de todos los artículos
            foreach ($items as $item) {
                $pId = $item['produktu_id'];
                // Usamos el id de producto para el bloqueo exclusivo
                $sql = "SELECT salgai, onarpen_egoera FROM produktuak WHERE id = :pid FOR UPDATE";
                $stmt = $db->prepare($sql);
                $stmt->execute([':pid' => $pId]);
                $producto = $stmt->fetch();

                if (!$producto || (int)$producto['salgai'] === 0 || $producto['onarpen_egoera'] !== 'onartua') {
                    // Artículo no disponible o no aprobado: quitar de la cesta y devolver error
                    $this->saskiaModel->removeItem((int)$item['saskia_id']);

                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }

                    $this->json([
                        'success' => false,
                        'error' => 'Zure saskiko artikuluren bat dagoeneko saldu da eta kendu egin da.'
                    ], 409);
                }
            }

            // Si llegamos aquí, todos están OK: proceder con la compra
            $productoIds = array_column($items, 'produktu_id');
            $total = 0;
            foreach ($items as $item) {
                $total += (float)$item['prezioa'];
            }

            if ($this->eskaerakModel->crear($userId, $productoIds, $total)) {
                $db->commit();
                $this->json(['success' => true, 'message' => 'Compra realizada correctamente']);
            } else {
                throw new Exception('Error al crear pedido');
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->json(['error' => 'Error al procesar pedido: ' . $e->getMessage()], 500);
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'procesar') {
    $controller = new EskaerakController();
    $controller->procesar();
}
