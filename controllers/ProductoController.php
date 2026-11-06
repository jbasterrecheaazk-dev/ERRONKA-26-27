<?php
/**
 * CONTROLLERS/PRODUCTOCONTROLLER.PHP
 *
 * Controlador para la creación y actualización de productos/artículos
 * desde el panel de perfil del usuario.
 *
 * Características:
 * - Soporta tanto peticiones AJAX (JSON) como navegación tradicional (redirecciones)
 * - Maneja subida de archivos (imágenes) con validación de tipo
 * - Al crear: productos van a estado 'pendentea' (esperando aprobación del admin)
 * - Al actualizar: solo permite editar productos en estado 'pendentea'
 * - Flash messages en sesión para feedback al usuario
 *
 * Endpoints utilizados:
 * - POST /perfil?action=create → crear o actualizar producto
 */

require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../models/ProductoModel.php';

/**
 * Clase que gestiona las operaciones CRUD de productos para los vendedores.
 * Solo permite edición de productos pendientes (no aprobados ni vendidos).
 */
class ProductoController
{
    /** @var ProductoModel Modelo de productos */
    private ProductoModel $model;

    /**
     * Constructor: inicializa el modelo de productos.
     */
    public function __construct()
    {
        $this->model = new ProductoModel();
    }

    /**
     * Determina si la petición proviene de JavaScript (fetch/XHR).
     * Se verifica mediante el header HTTP_X_REQUESTED_WITH.
     *
     * @return bool True si es una petición AJAX
     */
    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Punto de entrada principal: procesa creación o actualización.
     *
     * Si $_POST contiene edit_id → llama a update()
     * Si no → llama a store()
     *
     * Siempre verifica que:
     * - La petición sea POST
     * - El usuario esté autenticado
     *
     * @return void
     */
    public function create(): void
    {
        // Solo aceptar POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($this->isAjax()) {
                http_response_code(405);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Método no permitido']);
                exit;
            }
            header('Location: /perfil');
            exit;
        }

        // Verificar que el usuario tiene sesión iniciada
        if (!isset($_SESSION['usuario_id'])) {
            if ($this->isAjax()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'No autenticado']);
                exit;
            }
            header('Location: /login');
            exit;
        }

        $editId = (int)($_POST['edit_id'] ?? 0);
        if ($editId > 0) {
            $this->update($editId);
        } else {
            $this->store();
        }
    }

    /**
     * Crea un nuevo producto en la base de datos.
     *
     * Flujo:
     * 1. Recoge datos del formulario y los valida
     * 2. Procesa la imagen subida (validación de extensión JPG/PNG/WEBP)
     * 3. Inserta con salgai=0 y onarpen_egoera='pendentea'
     * 4. Responde con JSON (ajax) o redirige (navegación)
     *
     * Validaciones realizadas:
     * - Título, descripción y precio obligatorios
     * - Precio debe ser numérico y positivo
     * - Categoría obligatoria
     * - Imagen obligatoria con extensión permitida
     *
     * @return void
     */
    private function store(): void
    {
        $uploadDir = __DIR__ . '/../public/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Validación server-side de campos obligatorios
        $errors = [];

        $izenburua    = trim($_POST['izenburua'] ?? '');
        $deskribapena = trim($_POST['deskribapena'] ?? '');
        $prezioa      = trim($_POST['prezioa'] ?? '');
        $kategoria_id = trim($_POST['kategoria_id'] ?? '');
        $saltzaile_id = (int)$_SESSION['usuario_id'];

        $artista = trim($_POST['artista'] ?? '');
        $urtea   = trim($_POST['urtea'] ?? '');

        // Validar campos vacíos
        if (!$izenburua) $errors['izenburua'] = 'El título es obligatorio.';
        if (!$deskribapena) $errors['deskribapena'] = 'La descripción es obligatoria.';

        $prezioaVal = floatval($prezioa);
        if (!$prezioa || !is_numeric($prezioa) || $prezioaVal <= 0) {
            $errors['prezioa'] = 'Prezio baliogabea. Prezioak positiboa izan behar du.';
        }
        if (!$kategoria_id) $errors['kategoria_id'] = 'Debes seleccionar una categoría.';

        // Validar imagen
        if (!isset($_FILES['irudia']) || $_FILES['irudia']['error'] !== UPLOAD_ERR_OK) {
            $errors['irudia'] = 'Debes adjuntar una foto del artículo.';
        }

        // Si hay errores, responder según el tipo de petición
        if (!empty($errors)) {
            if ($this->isAjax()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => true, 'messages' => $errors]);
                exit;
            }
            foreach ($errors as $key => $msg) {
                $_SESSION['flash_errors'][$key] = $msg;
            }
            $_SESSION['flash_values'] = [
                'izenburua'    => $izenburua,
                'deskribapena' => $deskribapena,
                'prezioa'      => $prezioa,
                'kategoria_id' => $kategoria_id,
            ];
            header('Location: /perfil');
            exit;
        }

        $urtea = $urtea !== '' ? (int)$urtea : null;
        $artista = $artista !== '' ? $artista : null;

        $irudia = null;
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['irudia']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt, true)) {
            if ($this->isAjax()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => true, 'messages' => ['irudia' => 'Formato de imagen no válido. Usa JPG, PNG o WEBP.']]);
                exit;
            }
            $_SESSION['flash_errors']['irudia'] = 'Formato de imagen no válido.';
            header('Location: /perfil');
            exit;
        }

        $filename = uniqid('prod_') . '.' . $ext;
        $dest = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['irudia']['tmp_name'], $dest)) {
            $irudia = 'public/uploads/' . $filename;
        }

        try {
            $this->model->crear([
                'izenburua'         => $izenburua,
                'deskribapena'      => $deskribapena,
                'prezioa'           => $prezioa,
                'kategoria_id'      => (int)$kategoria_id,
                'saltzaile_id'      => $saltzaile_id,
                'artista'           => $artista,
                'urtea'             => $urtea,
                'irudia'            => $irudia,
                'salgai'            => 0,
                'onarpen_egoera'    => 'pendentea',
            ]);

            if ($this->isAjax()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true, 'message' => 'Artículo enviado correctamente.']);
                exit;
            }

            $_SESSION['flash_success'] = 'producto_creado';
            header('Location: /mis-articulos');
        } catch (Throwable $e) {
            if ($this->isAjax()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => true, 'messages' => ['general' => 'Error en el servidor. Inténtalo de nuevo más tarde.']]);
                exit;
            }
            $_SESSION['flash_error'] = 'error_base_datos';
            header('Location: /perfil');
        }
        exit;
    }

    /**
     * Actualiza los datos de un producto existente.
     *
     * Restricciones de edición:
     * - Solo el dueño del producto puede editarlo (verificación saltzaile_id)
     * - Solo se permiten editar productos en estado 'pendentea'
     *   (no se puede editar un producto aprobado, rechazado o vendido)
     *
     * La imagen es opcional: si no se envía nueva, se conserva la anterior.
     *
     * @param int $editId ID del producto a actualizar
     * @return void
     */
    private function update(int $editId): void
    {
        $uploadDir = __DIR__ . '/../public/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $producto = $this->model->obtenerPorId($editId);
        if ($producto === null || (int)$producto['saltzaile_id'] !== (int)$_SESSION['usuario_id']) {
            if ($this->isAjax()) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => true, 'messages' => ['general' => 'No tienes permiso para editar este artículo.']]);
                exit;
            }
            $_SESSION['flash_error'] = 'no_autorizado';
            header('Location: /mis-articulos');
            exit;
        }

        // BLOQUEO DE EDICIÓN: No permitir editar si el artículo ya fue aprobado, rechazado o vendido
        // Solo se permite la edición si el artículo se encuentra en estado 'pendentea'
        $onarpenEgoera = $producto['onarpen_egoera'] ?? 'pendentea';

        if ($onarpenEgoera !== 'pendentea') {
            $mensajeError = "Ezin da jaonartutako, baztertutako edo saldutako artikulurik editatu";
            if ($this->isAjax()) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => true, 'messages' => ['general' => $mensajeError]]);
                exit;
            }
            $_SESSION['flash_error'] = $mensajeError;
            header('Location: /perfil');
            exit;
        }

        // Validación server-side
        $errors = [];

        $izenburua    = trim($_POST['izenburua'] ?? '');
        $deskribapena = trim($_POST['deskribapena'] ?? '');
        $prezioa      = trim($_POST['prezioa'] ?? '');
        $kategoria_id = trim($_POST['kategoria_id'] ?? '');

        $artista = trim($_POST['artista'] ?? '');
        $urtea   = trim($_POST['urtea'] ?? '');

        if (!$izenburua) $errors['izenburua'] = 'El título es obligatorio.';
        if (!$deskribapena) $errors['deskribapena'] = 'La descripción es obligatoria.';

        $prezioaVal = floatval($prezioa);
        if (!$prezioa || !is_numeric($prezioa) || $prezioaVal <= 0) {
            $errors['prezioa'] = 'Prezio baliogabea. Prezioak positiboa izan behar du.';
        }
        if (!$kategoria_id) $errors['kategoria_id'] = 'Debes seleccionar una categoría.';

        if (!empty($errors)) {
            if ($this->isAjax()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => true, 'messages' => $errors]);
                exit;
            }
            foreach ($errors as $key => $msg) {
                $_SESSION['flash_errors'][$key] = $msg;
            }
            $_SESSION['flash_values'] = [
                'izenburua'    => $izenburua,
                'deskribapena' => $deskribapena,
                'prezioa'      => $prezioa,
                'kategoria_id' => $kategoria_id,
            ];
            header('Location: /perfil');
            exit;
        }

        $urtea  = $urtea  !== '' ? (int)$urtea  : null;
        $artista = $artista !== '' ? $artista : null;

        $irudia = $producto['irudia'] ?? null;

        if (isset($_FILES['irudia']) && $_FILES['irudia']['error'] === UPLOAD_ERR_OK) {
            $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['irudia']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExt, true)) {
                if ($this->isAjax()) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['error' => true, 'messages' => ['irudia' => 'Formato de imagen no válido. Usa JPG, PNG o WEBP.']]);
                    exit;
                }
                $_SESSION['flash_errors']['irudia'] = 'Formato de imagen no válido.';
                header("Location: /perfil");
                exit;
            }

            $filename = uniqid('prod_') . '.' . $ext;
            $dest = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['irudia']['tmp_name'], $dest)) {
                $irudia = 'public/uploads/' . $filename;
            }
        }

        try {
            $this->model->actualizar($editId, [
                'izenburua'    => $izenburua,
                'deskribapena' => $deskribapena,
                'prezioa'      => $prezioa,
                'kategoria_id' => (int)$kategoria_id,
                'artista'      => $artista,
                'urtea'        => $urtea,
                'irudia'       => $irudia,
            ]);

            if ($this->isAjax()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true, 'message' => 'Artículo actualizado correctamente.']);
                exit;
            }

            $_SESSION['flash_success'] = 'actualizado';
            header('Location: /mis-articulos');
        } catch (Throwable $e) {
            if ($this->isAjax()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => true, 'messages' => ['general' => 'Error en el servidor. Inténtalo de nuevo más tarde.']]);
                exit;
            }
            $_SESSION['flash_error'] = 'error_base_datos';
            header('Location: /mis-articulos');
        }
        exit;
    }
}
