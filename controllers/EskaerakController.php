<?php
/**
 * CONTROLLERS/ESKERARESKONTROLLER.PHP
 *
 * Controlador REST para procesar pedidos de compra (checkout).
 * Gestiona la transacción completa: verificación de disponibilidad,
 * confirmación de pedido y marcado de productos como vendidos.
 *
 * Nota de seguridad crítica:
 * Usa FOR UPDATE en SELECT para bloquear filas de productos durante
 * la transacción y prevenir condiciones de carrera (race condition)
 * cuando dos usuarios intentan comprar el mismo producto simultáneamente.
 *
 * Respuesta: Siempre JSON (controlador JSON-only).
 */

require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../models/SaskiaModel.php';
require_once __DIR__ . '/../models/EskaerakModel.php';

/**
 * Clase que orquesta el proceso de checkout de la cesta de compra.
 * Encapsula la lógica transaccional de la compra.
 */
class EskaerakController
{
    /** @var SaskiaModel Modelo de la cesta de compra */
    private SaskiaModel $saskiaModel;
    /** @var EskaerakModel Modelo de pedidos */
    private EskaerakModel $eskaerakModel;

    /**
     * Constructor: inicializa los modelos necesarios para el proceso.
     */
    public function __construct()
    {
        $this->saskiaModel = new SaskiaModel();
        $this->eskaerakModel = new EskaerakModel();
    }

    /**
     * Envía una respuesta JSON y termina la ejecución del script.
     * Limpia el output buffer primero para evitar problemas de cabeceras.
     *
     * @param array $data Array asociativo a serializar como JSON
     * @param int $status Código de estado HTTP (por defecto 200)
     * @return void
     */
    private function json(array $data, int $status = 200): void
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    /**
     * Verifica que el usuario esté autenticado y devuelve su ID.
     * Si no hay sesión activa, responde con JSON 401.
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

    /**
     * Procesa la compra de todos los productos en la cesta del usuario.
     *
     * Flujo completo (transacción PDO):
     * 1. Verifica que el usuario tenga items en la cesta
     * 2. Inicia transacción (beginTransaction)
     * 3. Por cada producto: bloquea con FOR UPDATE y verifica disponibilidad
     *    - Si algún producto ya no está disponible: rollback + error 409
     * 4. Calcula el total y llama al modelo para registrar el pedido
     * 5. Commit de la transacción + respuesta de éxito 200
     *
     * @return void Respuesta JSON siempre termina la ejecución
     */
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

            // Inyectar la conexión transaccional en el modelo para evitar deadlocks
            $this->eskaerakModel->setDb($db);

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

// Enrutador: solo procesa cuando la acción es 'procesar'
if (isset($_GET['action']) && $_GET['action'] === 'procesar') {
    $controller = new EskaerakController();
    $controller->procesar();
}
