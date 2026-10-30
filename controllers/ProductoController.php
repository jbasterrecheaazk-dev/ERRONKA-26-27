<?php
/*
 * CONTROLLERS/PRODUCTOCONTROLLER.PHP
 *
 * Controlador para gestionar la creación y actualización de
 * productos/artículos en venta desde el panel de perfil.
 */

require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../models/ProductoModel.php';

class ProductoController
{
    private ProductoModel $model;

    public function __construct()
    {
        $this->model = new ProductoModel();
    }

    /*
     * isAjax()
     * Determina si la petición proviene de JavaScript (fetch/XHR).
     */
    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /*
     * create()
     * Procesa el formulario de venta o edición:
     * - Si viene edit_id, actualiza el producto existente.
     * - Si no viene, crea un nuevo producto.
     * - Si la petición es AJAX, responde con JSON en lugar de redirigir.
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
        if (!$prezioa || !is_numeric($prezioa) || (float)$prezioa <= 0) {
            $errors['prezioa'] = 'El precio debe ser un número mayor que 0.';
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
        if (!$prezioa || !is_numeric($prezioa) || (float)$prezioa <= 0) {
            $errors['prezioa'] = 'El precio debe ser un número mayor que 0.';
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
