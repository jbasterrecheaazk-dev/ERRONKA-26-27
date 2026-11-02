<?php
/*
 * CONTROLLERS/SASKIACONTROLLER.PHP
 *
 * Controlador REST en formato JSON para gestionar la cesta de compra
 * mediante AJAX/Fetch API (DWEC). Todas las respuestas son JSON puro.
 */

require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../models/SaskiaModel.php';

class SaskiaController
{
    private SaskiaModel $model;

    public function __construct()
    {
        $this->model = new SaskiaModel();
    }

    // ------------------------------------------------------------------
    // Utilidades comunes
    // ------------------------------------------------------------------

    /**
     * Limpia cualquier salida previa y envuelve la respuesta JSON.
     */
    private function json(array $data, int $status = 200): void
    {
        if (ob_get_level()) {
            ob_clean();
        }
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    /**
     * Exige que el usuario esté autenticado; si no, devuelve 401.
     */
    private function requireAuth(): int
    {
        if (!isset($_SESSION['usuario_id'])) {
            $this->json(['error' => 'Unauthenticated'], 401);
        }
        return (int)$_SESSION['usuario_id'];
    }

    // ------------------------------------------------------------------
    // GET /api/saskia
    // ------------------------------------------------------------------

    public function index(): void
    {
        $userId = $this->requireAuth();

        $items = $this->model->getItems($userId);

        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += (float)$item['prezioa'];
        }

        $this->json([
            'items'    => $items,
            'total'    => number_format($subtotal, 2, '.', ''),
            'count'    => count($items),
        ]);
    }

    // ------------------------------------------------------------------
    // POST /api/saskia/gehitu
    // ------------------------------------------------------------------

    public function gehitu(): void
    {
        $userId = $this->requireAuth();

        $productId = (int)($_POST['produktu_id'] ?? 0);

        if ($productId <= 0) {
            $this->json(['error' => 'produktu_id inválido'], 400);
        }

        // Si el producto ya está en la cesta, rechazo en vez de duplicar
        // (mercado de segunda mano: solo una unidad por artículo)
        $sqlCheck = "SELECT id FROM saskia WHERE erabiltzaile_id = :uid AND produktu_id = :pid LIMIT 1";
        $stmt = $this->model->getDb()->prepare($sqlCheck);
        $stmt->execute([':uid' => $userId, ':pid' => $productId]);
        if ($stmt->fetch()) {
            $this->json(['error' => 'El producto ya está en la cesta'], 400);
        }

        $this->model->addItem($userId, $productId, 1);

        $this->json([
            'success' => true,
            'message' => 'Artículo añadido a la cesta',
        ]);
    }

    // ------------------------------------------------------------------
    // POST /api/saskia/kendu
    // ------------------------------------------------------------------

    public function kendu(): void
    {
        $userId = $this->requireAuth();

        $saskiaId = (int)($_POST['saskia_id'] ?? 0);
        $productId = (int)($_POST['produktu_id'] ?? 0);

        if ($saskiaId > 0) {
            $this->model->removeItem($saskiaId);
        } elseif ($productId > 0) {
            // Eliminar por producto: buscar la fila del usuario
            $sql = "SELECT id FROM saskia WHERE erabiltzaile_id = :uid AND produktu_id = :pid LIMIT 1";
            $stmt = $this->model->getDb()->prepare($sql);
            $stmt->execute([':uid' => $userId, ':pid' => $productId]);
            $row = $stmt->fetch();
            if ($row) {
                $this->model->removeItem((int)$row['id']);
            }
        } else {
            $this->json(['error' => 'Parámetros insuficientes'], 400);
        }

        $this->json(['success' => true, 'message' => 'Artículo eliminado de la cesta']);
    }
}

/*
 * ROUTING POR ?action=
 * Cada endpoint del controlador se invoca según la acción solicitada.
 */

if (isset($_GET['action'])) {
    $controller = new SaskiaController();
    switch ($_GET['action']) {
        case 'index':
            $controller->index();
            break;
        case 'gehitu':
            $controller->gehitu();
            break;
        case 'kendu':
            $controller->kendu();
            break;
    }
}