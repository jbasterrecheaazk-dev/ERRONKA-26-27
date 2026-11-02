<?php
/*
 * CONTROLLERS/ADMINCONTROLLER.PHP
 *
 * Panel de administración: gestión de solicitudes de tasación,
 * aprobación/publicación de productos y estadísticas del marketplace.
 * Solo accesible por usuarios con rol = 'admin'.
 */

require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../models/ProductoModel.php';
require_once __DIR__ . '/../models/EskaerakModel.php';
require_once __DIR__ . '/../models/ErabiltzaileaModel.php';

class AdminController
{
    private PDO $db;
    private ProductoModel $productoModel;
    private EskaerakModel $eskaerakModel;
    private ErabiltzaileaModel $erabiltzaileaModel;

    public function __construct()
    {
        $this->db = (new \Conexion())->getConexion();
        $this->productoModel = new ProductoModel();
        $this->eskaerakModel = new EskaerakModel();
        $this->erabiltzaileaModel = new ErabiltzaileaModel();
    }

    // ------------------------------------------------------------------
    // Security: strict admin verification
    // ------------------------------------------------------------------
    private function requireAdmin(): void
    {
        // session_start() ya se llama en index.php (front controller)
        if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
            // Si es petición AJAX/API, devolver JSON 403
            if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['error' => 'Acceso denegado: se requiere rol de administrador']);
                exit;
            }
            // Si es navegación web, redirigir a inicio con 403
            http_response_code(403);
            header('Location: /');
            exit;
        }
    }

    // ------------------------------------------------------------------
    // GET /admin - Panel principal de administración
    // ------------------------------------------------------------------
    public function index(): void
    {
        $this->requireAdmin();

        // 1. Solicitudes pendientes (productos con onarpen_egoera = 'pendentea')
        $pendientes = $this->productoModel->lortuPendenteak();

        // 2. Estadísticas globales para el dashboard
        $estadisticas = $this->eskaerakModel->lortuEstatistikoak();

        // 3. Últimos pedidos para actividad reciente
        $azkenEskaerak = $this->eskaerakModel->lortuAzkenEskaerak(5);

        // 4. Contador de pendientes para badge
        $contadorPendientes = count($pendientes);

        require_once __DIR__ . '/../views/pages/admin.php';
    }

    // ------------------------------------------------------------------
    // POST /api/admin/onartu - Aprobar producto (AJAX)
    // ------------------------------------------------------------------
    public function onartu(): void
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int) ($input['id'] ?? 0);

        if ($id <= 0) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => 'ID inválido']);
            exit;
        }

        // Verificar que el producto existe y está pendiente
        $sqlCheck = "SELECT onarpen_egoera FROM produktuak WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sqlCheck);
        $stmt->execute([':id' => $id]);
        $actual = $stmt->fetchColumn();

        if ($actual !== 'pendentea') {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => 'El producto no está pendiente de aprobación']);
            exit;
        }

        // Aprobar: onarpen_egoera = 'onartua', salgai = 1
        $ok = $this->productoModel->eguneratuOnarpenEgoera($id, 'onartua', true);

        header('Content-Type: application/json');
        if ($ok) {
            echo json_encode(['success' => true, 'message' => 'Producto aprobado y publicado en catálogo']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al aprobar el producto']);
        }
        exit;
    }

    // ------------------------------------------------------------------
    // POST /api/admin/baztertu - Rechazar producto (AJAX)
    // ------------------------------------------------------------------
    public function baztertu(): void
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int) ($input['id'] ?? 0);

        if ($id <= 0) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => 'ID inválido']);
            exit;
        }

        // Verificar que el producto existe y está pendiente
        $sqlCheck = "SELECT onarpen_egoera FROM produktuak WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sqlCheck);
        $stmt->execute([':id' => $id]);
        $actual = $stmt->fetchColumn();

        if ($actual !== 'pendentea') {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => 'El producto no está pendiente de aprobación']);
            exit;
        }

        // Rechazar: onarpen_egoera = 'baztertua', salgai = 0
        $ok = $this->productoModel->eguneratuOnarpenEgoera($id, 'baztertua', false);

        header('Content-Type: application/json');
        if ($ok) {
            echo json_encode(['success' => true, 'message' => 'Producto rechazado']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al rechazar el producto']);
        }
        exit;
    }
}

// Routing legacy para compatibilidad
if (isset($_GET['action'])) {
    $controller = new AdminController();
    switch ($_GET['action']) {
        case 'onartu':
            $controller->onartu();
            break;
        case 'baztertu':
            $controller->baztertu();
            break;
    }
}