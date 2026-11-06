<?php
/**
 * CONTROLLERS/SASKIACONTROLLER.PHP
 *
 * Controlador REST para gestionar la cesta de compra (saskia) mediante API JSON.
 * Todas las respuestas son JSON puro, pensado para consumo por fetch API desde JS.
 *
 * Endpoints disponibles:
 * - GET  /api/saskia          → listar items del carrito con subtotal
 * - POST /api/saskia/gehitu   → añadir producto al carrito (si no está duplicado)
 * - POST /api/saskia/kendu    → eliminar producto del carrito
 *
 * Nota de negocio: mercado C2C de segunda mano → un producto solo puede existir
 * una vez en la cesta de un usuario (no hay cantidades > 1 para el mismo producto).
 */

require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../models/SaskiaModel.php';

/**
 * Clase que orquesta las operaciones de la cesta de compra.
 * Todas sus respuestas son JSON con códigos de estado HTTP apropiados.
 */
class SaskiaController
{
    /** @var SaskiaModel Modelo de la cesta de compra */
    private SaskiaModel $model;

    /**
     * Constructor: inicializa el modelo de cesta.
     */
    public function __construct()
    {
        $this->model = new SaskiaModel();
    }

    // ------------------------------------------------------------------
    // Utilidades comunes
    // ------------------------------------------------------------------

    /**
     * Limpia cualquier salida previa y envuelve la respuesta JSON.
     *
     * @param array $data Array con los datos a devolver
     * @param int $status Código HTTP de respuesta (por defecto 200 OK)
     * @return void
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
     * Verifica que el usuario esté autenticado y devuelve su ID.
     * Si no hay sesión activa, responde con JSON 401 Unauthorized.
     *
     * @return int ID del usuario autenticado
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

    /**
     * Lista todos los items de la cesta del usuario con su subtotal.
     *
     * Respuesta JSON:
     * {
     *   "items": [...],        // array de productos con datos completos
     *   "total": "XX.XX",      // subtotal formateado con 2 decimales
     *   "count": N             // número de items distintos
     * }
     *
     * @return void
     */
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

    /**
     * Añade un producto a la cesta del usuario.
     * Si el producto ya está en la cesta, devuelve error 400 en lugar de duplicar.
     *
     * Payload esperado: producto_id (entero) en body de la petición
     *
     * @return void
     */
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

    /**
     * Elimina un item de la cesta.
     * Aceps dos formas de identificar el item a eliminar:
     * - saskia_id: ID directo del registro en la tabla saskia
     * - producto_id: ID del producto (busca la fila correspondiente del usuario)
     *
     * @return void
     */
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
