<?php
/**
 * CONTROLLERS/ADMINCONTROLLER.PHP
 *
 * Panel de administración para la gestión del marketplace.
 * Solo accesible por usuarios con rol = 'admin'.
 *
 * Funcionalidades:
 * - Dashboard principal con estadísticas y productos pendientes
 * - Aprobar productos pendientes (onarpen_egoera → 'onartua', salgai → 1)
 * - Rechazar productos pendientes (onarpen_egoera → 'baztertua', salgai → 0)
 * - Eliminar productos aprobados del catálogo
 *
 * Respuestas:
 * - GET /admin → devuelve la vista admin.php
 * - POST /api/admin/onartu → JSON de aprobación
 * - POST /api/admin/baztertu → JSON de rechazo
 * - POST /api/admin/ezabatu → JSON de eliminación
 */

require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../models/ProductoModel.php';
require_once __DIR__ . '/../models/EskaerakModel.php';
require_once __DIR__ . '/../models/ErabiltzaileaModel.php';

/**
 * Clase que administra las operaciones del panel de control.
 * Verifica permisos de administrador antes de ejecutar cualquier acción.
 */
class AdminController
{
    /** @var PDO Conexión a la base de datos */
    private PDO $db;
    /** @var ProductoModel Modelo de productos */
    private ProductoModel $productoModel;
    /** @var EskaerakModel Modelo de pedidos */
    private EskaerakModel $eskaerakModel;
    /** @var ErabiltzaileaModel Modelo de usuarios */
    private ErabiltzaileaModel $erabiltzaileaModel;

    /**
     * Constructor: inicializa las dependencias del controlador.
     */
    public function __construct()
    {
        $this->db = (new \Conexion())->getConexion();
        $this->productoModel = new ProductoModel();
        $this->eskaerakModel = new EskaerakModel();
        $this->erabiltzaileaModel = new ErabiltzaileaModel();
    }

    /**
     * Verifica que el usuario actual tenga rol de administrador.
     *
     * Si la petición es AJAX (/api/), responde con JSON 403.
     * Si es navegación web, redirige a la página principal con 403.
     *
     * @throws never Esta función nunca retorna; siempre llama a exit().
     */
    private function requireAdmin(): void
    {
        // session_start() ya se llama en index.php (front controller)
        if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
            // Si es petición AJAX/API, devolver JSON 403
            if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Baimenik gabe']);
                exit;
            }
            // Si es navegación web, redirigir a inicio con 403
            http_response_code(403);
            header('Location: /');
            exit;
        }
    }

    /**
     * Muestra el panel de administración principal.
     *
     * Carga:
     * 1. Productos pendientes de aprobación (lortuPendenteak)
     * 2. Productos aprobados y activos (obtenerTodos)
     * 3. Estadísticas globales del marketplace
     * 4. Últimos 5 pedidos realizados
     *
     * @return void Renderiza la vista admin.php
     */
    public function index(): void
    {
        $this->requireAdmin();

        // 1. Solicitudes pendientes (productos con onarpen_egoera = 'pendentea')
        $pendientes = $this->productoModel->lortuPendenteak();

        // 2. Productos aceptados/activos (onarpen_egoera = 'onartua')
        $aprobados = $this->productoModel->obtenerTodos();

        // 3. Estadísticas globales para el dashboard
        $estadisticas = $this->eskaerakModel->lortuEstatistikoak();

        // 4. Últimos pedidos para actividad reciente
        $azkenEskaerak = $this->eskaerakModel->lortuAzkenEskaerak(5);

        // 5. Contador de pendientes para badge
        $contadorPendientes = count($pendientes);

        require_once __DIR__ . '/../views/pages/admin.php';
    }

    /**
     * Aprueba un producto pendiente y lo publica en el catálogo.
     *
     * Endpoint: POST /api/admin/onartu
     * Payload JSON: { "id": <producto_id> }
     *
     * Flujo:
     * 1. Verifica rol de administrador
     * 2. Verifica que el producto existe y está pendiente (pendentea)
     * 3. Actualiza onarpen_egoera a 'onartua' y salgai a 1
     *
     * @return void Respuesta JSON siempre termina la ejecución
     */
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

    /**
     * Rechaza un producto pendiente y lo marca como no disponible.
     *
     * Endpoint: POST /api/admin/baztertu
     * Payload JSON: { "id": <producto_id> }
     *
     * Flujo:
     * 1. Verifica rol de administrador
     * 2. Verifica que el producto existe y está pendiente (pendentea)
     * 3. Actualiza onarpen_egoera a 'baztertua' y salgai a 0
     *
     * @return void Respuesta JSON siempre termina la ejecución
     */
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

    /**
     * Elimina permanentemente un producto aprobado del catálogo.
     *
     * Endpoint: POST /api/admin/ezabatu
     * Payload JSON: { "id": <producto_id> }
     *
     * Flujo:
     * 1. Verifica rol de administrador
     * 2. Verifica que el producto existe y está aprobado (onartua)
     * 3. Ejecuta DELETE en la tabla productoak
     *
     * @return void Respuesta JSON siempre termina la ejecución
     */
    public function ezabatu(): void
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Método no permitido']);
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

        // Solo se pueden eliminar productos ya aprobados
        $sqlCheck = "SELECT onarpen_egoera, izenburua FROM produktuak WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sqlCheck);
        $stmt->execute([':id' => $id]);
        $producto = $stmt->fetch();

        if (!$producto || $producto['onarpen_egoera'] !== 'onartua') {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => 'El producto no existe o no está aprobado']);
            exit;
        }

        $ok = $this->productoModel->ezabatuProduktua($id);

        header('Content-Type: application/json');
        if ($ok) {
            echo json_encode(['success' => true, 'message' => 'Producto eliminado correctamente']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar el producto']);
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
