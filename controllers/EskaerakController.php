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

        $productoIds = array_column($items, 'produktu_id');
        $total = array_sum(array_column($items, 'prezioa'));

        if ($this->eskaerakModel->crear($userId, $productoIds, $total)) {
            $this->json(['success' => true, 'message' => 'Compra realizada correctamente']);
        } else {
            $this->json(['error' => 'Error al procesar pedido'], 500);
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'procesar') {
    $controller = new EskaerakController();
    $controller->procesar();
}
