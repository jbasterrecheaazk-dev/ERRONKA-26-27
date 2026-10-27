<?php
/*
 * CONTROLLERS/PRODUCTOCONTROLLER.PHP
 *
 * Controlador para gestionar la creación de productos/artículos en venta.
 * Recibe los datos del formulario de "Vender artículo" y los guarda en BD.
 */

session_start();

require_once __DIR__ . '/../models/ProductoModel.php';

class ProductoController
{
    private ProductoModel $model;

    public function __construct()
    {
        $this->model = new ProductoModel();
    }

    /*
     * create()
     * Procesa el formulario de venta: valida, maneja la imagen subida
     * y guarda el producto en la base de datos.
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

        // Recoger datos del formulario
        $izenburua   = trim($_POST['izenburua'] ?? '');
        $deskribapena = trim($_POST['deskribapena'] ?? '');
        $prezioa     = trim($_POST['prezioa'] ?? '');
        $kategoria_id= trim($_POST['kategoria_id'] ?? '');
        $saltzaile_id= (int)$_SESSION['usuario_id'];
        // Campos condicionales (opcionales)
        $artista = trim($_POST['artista'] ?? '');
        $urtea   = trim($_POST['urtea'] ?? '');
        $egoera  = trim($_POST['egoera'] ?? '');

        // Validación básica
        if (!$izenburua || !$deskribapena || !$prezioa || !$kategoria_id) {
            header('Location: /perfil?error=datos_incompletos');
            exit;
        }

        // Normalizar valores opcionales
        $urtea = $urtea !== '' ? (int)$urtea : null;
        $artista = $artista !== '' ? $artista : null;
        $egoera  = $egoera  !== '' ? $egoera  : null;

        // Manejar subida de imagen
        $irudia = null;
        if (isset($_FILES['irudia']) && $_FILES['irudia']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/img/productos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = pathinfo($_FILES['irudia']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('prod_') . '.' . $ext;
            $dest = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['irudia']['tmp_name'], $dest)) {
                $irudia = 'public/img/productos/' . $filename;
            }
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
            header('Location: /perfil?success=producto_creado');
        } catch (PDOException $e) {
            error_log('Error al crear producto: ' . $e->getMessage());
            header('Location: /perfil?error=error_bd');
        }
        exit;
    }
}

// Routing
if (isset($_GET['action'])) {
    $controller = new ProductoController();
    switch ($_GET['action']) {
        case 'create':
            $controller->create();
            break;
    }
}
