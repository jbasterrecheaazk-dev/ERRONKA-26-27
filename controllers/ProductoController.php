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

    public function jsonGet(int $id): void
    {
        // Limpiar cualquier buffer de salida previo para evitar basura en la respuesta JSON
        if (ob_get_level()) {
            ob_clean();
        }
        header('Content-Type: application/json');
        if (!isset($_SESSION['usuario_id'])) {
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }
        $producto = $this->model->obtenerPorId($id);

        if (!$producto || (int)$producto['saltzaile_id'] !== (int)$_SESSION['usuario_id']) {
            echo json_encode(['error' => 'No autorizado o no encontrado']);
            exit;
        }

        echo json_encode($producto);
        exit;
    }

    /*
     * create()
     * Procesa el formulario de venta o edición:
     * - Si viene edit_id, actualiza el producto existente.
     * - Si no viene, crea un nuevo producto.
     */
    public function create(): void
    {
        // Solo aceptar POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /perfil');
            exit;
        }

        // Verificar que el usuario tiene sesión iniciada
        if (!isset($_SESSION['usuario_id'])) {
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

        if (!isset($_FILES['irudia']) || $_FILES['irudia']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'error_subida_imagen';
            header('Location: /perfil');
            exit;
        }

        $izenburua    = trim($_POST['izenburua'] ?? '');
        $deskribapena = trim($_POST['deskribapena'] ?? '');
        $prezioa      = trim($_POST['prezioa'] ?? '');
        $kategoria_id = trim($_POST['kategoria_id'] ?? '');
        $saltzaile_id = (int)$_SESSION['usuario_id'];

        $artista = trim($_POST['artista'] ?? '');
        $urtea   = trim($_POST['urtea'] ?? '');
        $egoera  = trim($_POST['egoera'] ?? '');

        if (!$izenburua || !$deskribapena || !$prezioa || !$kategoria_id) {
            $_SESSION['flash_error'] = 'datos_incompletos';
            header('Location: /perfil');
            exit;
        }

        $urtea = $urtea !== '' ? (int)$urtea : null;
        $artista = $artista !== '' ? $artista : null;
        $egoera  = $egoera  !== '' ? $egoera  : null;

        $irudia = null;
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['irudia']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt, true)) {
            $_SESSION['flash_error'] = 'extension_no_valida';
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
                'izenburua'    => $izenburua,
                'deskribapena' => $deskribapena,
                'prezioa'      => $prezioa,
                'kategoria_id' => (int)$kategoria_id,
                'saltzaile_id' => $saltzaile_id,
                'artista'      => $artista,
                'urtea'        => $urtea,
                'egoera'       => $egoera,
                'irudia'       => $irudia,
            ]);
            $_SESSION['flash_success'] = 'producto_creado';
            header('Location: /mis-articulos');
        } catch (Throwable $e) {
            die('Error al guardar en base de datos: ' . $e->getMessage());
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
            $_SESSION['flash_error'] = 'no_autorizado';
            header('Location: /mis-articulos');
            exit;
        }

        $izenburua    = trim($_POST['izenburua'] ?? '');
        $deskribapena = trim($_POST['deskribapena'] ?? '');
        $prezioa      = trim($_POST['prezioa'] ?? '');
        $kategoria_id = trim($_POST['kategoria_id'] ?? '');

        $artista = trim($_POST['artista'] ?? '');
        $urtea   = trim($_POST['urtea'] ?? '');
        $egoera  = trim($_POST['egoera'] ?? '');

        if (!$izenburua || !$deskribapena || !$prezioa || !$kategoria_id) {
            $_SESSION['flash_error'] = 'datos_incompletos';
            header('Location: /mis-articulos');
            exit;
        }

        $urtea  = $urtea  !== '' ? (int)$urtea  : null;
        $artista = $artista !== '' ? $artista : null;
        $egoera = $egoera !== '' ? $egoera  : null;

        $irudia = $producto['irudia'] ?? null;

        if (isset($_FILES['irudia']) && $_FILES['irudia']['error'] === UPLOAD_ERR_OK) {
            $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['irudia']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExt, true)) {
                $_SESSION['flash_error'] = 'extension_no_valida';
                header("Location: /mis-articulos");
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
                'egoera'       => $egoera,
                'irudia'       => $irudia,
            ]);
            $_SESSION['flash_success'] = 'actualizado';
            header('Location: /mis-articulos');
        } catch (Throwable $e) {
            die('Error al actualizar en base de datos: ' . $e->getMessage());
        }
        exit;
    }
}
