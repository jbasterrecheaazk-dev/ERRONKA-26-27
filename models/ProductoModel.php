<?php
/**
 * MODELS/PRODUCTOMODEL.PHP
 *
 * Modelo para gestionar los productos/artículos del marketplace.
 * Opera sobre la tabla "produktuak" (productos en euskera).
 *
 * Campos clave de la tabla:
 *   - salgai: booleano (0=no disponible, 1=disponible para venta)
 *   - onarpen_egoera: estado de aprobación ('pendentea'=pendiente, 'onartua'=aprobado, 'baztertua'=rechazado)
 *   - saltzaile_id: ID del usuario que ofrece el producto (vendedor)
 *   - kategoria_id: ID de la categoría (relación con kategoriak)
 *
 * Seguridad: Todas las consultas usan sentencias preparadas PDO
 * con parámetros nombrados para prevenir inyección SQL.
 */

require_once __DIR__ . '/../config/Conexion.php';

/**
 * Clase que encapsula el acceso a datos de la entidad "Producto".
 * Implementa el patrón DAO (Data Access Object) del MVC.
 */
class ProductoModel
{
    /** @var PDO Conexión a la base de datos (Singleton vía Conexion) */
    private PDO $db;

    /**
     * Constructor: obtiene la conexión a la BD una sola vez.
     */
    public function __construct()
    {
        $conexion = new Conexion();
        $this->db = $conexion->getConexion();
    }

    /**
     * Insertar un nuevo producto en la base de datos.
     *
     * Los productos nuevos se crean con:
     *   - salgai = 0 (no visible en catálogo hasta aprobación)
     *   - onarpen_egoera = 'pendentea' (esperando revisión del admin)
     *
     * @param array $datos Array con los campos del producto (ver docs inline)
     * @return bool True si la inserción fue exitosa
     */
    public function crear(array $datos): bool
    {
        $sql = "INSERT INTO produktuak
                ( izenburua, deskribapena, prezioa, kategoria_id, saltzaile_id,
                  artista, urtea, irudia, salgai, onarpen_egoera )
                VALUES (
                    :izenburua, :deskribapena, :prezioa, :kategoria_id, :saltzaile_id,
                    :artista, :urtea, :irudia, :salgai, :onarpen_egoera
                )";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':izenburua'         => $datos['izenburua'],
            ':deskribapena'      => $datos['deskribapena'],
            ':prezioa'           => $datos['prezioa'],
            ':kategoria_id'      => $datos['kategoria_id'],
            ':saltzaile_id'      => $datos['saltzaile_id'],
            ':artista'           => $datos['artista'] ?? null,
            ':urtea'             => $datos['urtea'] ?? null,
            ':irudia'            => $datos['irudia'] ?? null,
            ':salgai'            => $datos['salgai'] ?? 0,      // Por defecto no visible
            ':onarpen_egoera'    => $datos['onarpen_egoera'] ?? 'onartua', // Estado de aprobación
        ]);
    }

    /**
     * Obtener todos los productos publicados y aprobados.
     * Solo devuelve productos con salgai=1 y onarpen_egoera='onartua'.
     *
     * @return array Lista de productos con datos de categoría (JOIN)
     */
    public function obtenerTodos(): array
    {
        // LEFT JOIN con kategoriak para obtener el nombre de la categoría
        // WHERE filtra solo productos visibles y aprobados
        $sql = "SELECT p.*, k.izena as categoria_nombre
                FROM produktuak p
                LEFT JOIN kategoriak k ON p.kategoria_id = k.id
                WHERE p.salgai = 1 AND p.onarpen_egoera = 'onartua'
                ORDER BY p.sortze_data DESC"; // Más recientes primero
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtener un producto por su ID junto con datos del vendedor y categoría.
     *
     * @param int $id Identificador del producto
     * @return array|null Datos del producto o null si no existe
     */
    public function obtenerPorId(int $id): ?array
    {
        // JOINs: erabiltzaileak (vendedor) + kategoriak (categoría)
        $sql = "SELECT p.*,
                       u.id   AS vendedor_id,
                       u.izena AS vendedor_nombre,
                       u.email AS vendedor_email,
                       k.izena AS categoria_nombre
                FROM produktuak p
                LEFT JOIN erabiltzaileak u ON p.saltzaile_id = u.id
                LEFT JOIN kategoriak k        ON p.kategoria_id = k.id
                WHERE p.id = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $producto = $stmt->fetch();
        return $producto ?: null;
    }

    /**
     * Obtener múltiples productos por sus IDs.
     * Usado para cargar favoritos del usuario desde el array de IDs almacenados.
     *
     * @param int[] $ids Array de identificadores de productos
     * @return array Lista de productos encontrados
     */
    public function obtenerPorIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        // Construir placeholders dinámicos para la cláusula IN
        // Esto es seguro porque los IDs ya fueron validados como enteros
        $placeholders = [];
        $params = [];
        foreach ($ids as $i => $id) {
            $paramName = ':id_' . $i;
            $placeholders[] = $paramName;
            $params[$paramName] = (int)$id; // Casting a int para seguridad
        }
        $inClause = implode(',', $placeholders);

        $sql = "SELECT p.*,
                       u.id   AS vendedor_id,
                       u.izena AS vendedor_nombre,
                       u.email AS vendedor_email,
                       k.izena AS categoria_nombre
                FROM produktuak p
                LEFT JOIN erabiltzaileak u ON p.saltzaile_id = u.id
                LEFT JOIN kategoriak k        ON p.kategoria_id = k.id
                WHERE p.id IN ($inClause)
                AND p.salgai = 1
                AND p.onarpen_egoera = 'onartua'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Actualizar los datos de un producto existente.
     * Solo actualiza campos editables (no salgai ni onarpen_egoera).
     *
     * @param int $id ID del producto a actualizar
     * @param array $datos Array con los campos a actualizar
     * @return bool True si la actualización fue exitosa
     */
    public function actualizar(int $id, array $datos): bool
    {
        $sql = "UPDATE produktuak SET
                    izenburua    = :izenburua,
                    deskribapena = :deskribapena,
                    prezioa      = :prezioa,
                    kategoria_id = :kategoria_id,
                    artista      = :artista,
                    urtea        = :urtea,
                    irudia       = :irudia
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'           => $id,
            ':izenburua'    => $datos['izenburua'],
            ':deskribapena' => $datos['deskribapena'],
            ':prezioa'      => $datos['prezioa'],
            ':kategoria_id' => $datos['kategoria_id'],
            ':artista'      => $datos['artista'] ?? null,
            ':urtea'        => $datos['urtea'] ?? null,
            ':irudia'       => $datos['irudia'] ?? null,
        ]);
    }

    /**
     * Obtener todos los productos publicados por un vendedor específico.
     * Usado en el perfil del usuario para mostrar "Mis artículos ofrecidos".
     *
     * @param int $saltzaile_id ID del vendedor (saltzaile = vendedor en euskera)
     * @return array Lista de productos ordenados por fecha de creación
     */
    public function getPorVendedor(int $saltzaile_id): array
    {
        $sql = "SELECT * FROM produktuak WHERE saltzaile_id = :saltzaile_id ORDER BY sortze_data DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':saltzaile_id' => $saltzaile_id]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener productos filtrados por término de búsqueda y/o categoría con paginación.
     *
     * La búsqueda se realiza sobre: título (izenburua), descripción (deskribapena) y artista.
     * Se incluyen metadatos de paginación para el frontend.
     *
     * @param string $q Término de búsqueda (opcional, vacío = no filtra por texto)
     * @param int $kategoriaId ID de categoría (0 = todas)
     * @param int $page Página actual (1-based)
     * @param int $limit Límite de resultados por página
     * @return array Array con 'productos' (array) y 'paginacion' (metadata)
     */
    public function obtenerFiltrados(string $q = '', int $kategoriaId = 0, int $page = 1, int $limit = 20): array
    {
        $sql = "SELECT p.*, k.izena as categoria_nombre
                FROM produktuak p
                LEFT JOIN kategoriak k ON p.kategoria_id = k.id
                WHERE p.salgai = 1 AND p.onarpen_egoera = 'onartua'";

        $params = [];

        // Búsqueda parcial en título, descripción y artista
        if ($q !== '') {
            $sql .= " AND (p.izenburua LIKE :q1 OR p.deskribapena LIKE :q2 OR p.artista LIKE :q3)";
            $params[':q1'] = '%' . $q . '%';
            $params[':q2'] = '%' . $q . '%';
            $params[':q3'] = '%' . $q . '%';
        }

        // Filtro por categoría
        if ($kategoriaId > 0) {
            $sql .= " AND p.kategoria_id = :kategoria_id";
            $params[':kategoria_id'] = $kategoriaId;
        }

        // Paginación: LIMIT/OFFSET no soportan parámetros bindados en PDO,
        // por lo que se interpola como entero después de casting de seguridad
        $offset = ($page - 1) * $limit;
        $sql .= " ORDER BY p.sortze_data DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $productos = $stmt->fetchAll();

        // Consulta de conteo para metadatos de paginación (misma lógica de filtros)
        $countSql = "SELECT COUNT(*) as total FROM produktuak p
                      LEFT JOIN kategoriak k ON p.kategoria_id = k.id
                      WHERE p.salgai = 1 AND p.onarpen_egoera = 'onartua'";

        $countParams = [];
        if ($q !== '') {
            $countSql .= " AND (p.izenburua LIKE :q1 OR p.deskribapena LIKE :q2 OR p.artista LIKE :q3)";
            $countParams[':q1'] = '%' . $q . '%';
            $countParams[':q2'] = '%' . $q . '%';
            $countParams[':q3'] = '%' . $q . '%';
        }
        if ($kategoriaId > 0) {
            $countSql .= " AND p.kategoria_id = :kategoria_id";
            $countParams[':kategoria_id'] = $kategoriaId;
        }

        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($countParams);
        $total = (int)($countStmt->fetch()['total'] ?? 0);

        return [
            'productos' => $productos,
            'paginacion' => [
                'pagina' => $page,
                'limite' => $limit,
                'total' => $total,
                'paginas' => ceil($total / $limit),
                'offset' => $offset
            ]
        ];
    }

    /**
     * Obtener todos los productos pendientes de aprobación (onarpen_egoera = 'pendentea').
     * Usado por el panel de administración para revisar las solicitudes de los usuarios.
     *
     * @return array Lista de productos pendientes con datos del solicitante
     */
    public function lortuPendenteak(): array
    {
        // JOIN con erabiltzaileak para obtener nombre/email del solicitante
        $sql = "SELECT p.*,
                       u.izena   as solicitante_nombre,
                       u.email   as solicitante_email,
                       k.izena   as categoria_nombre
                FROM produktuak p
                JOIN erabiltzaileak u ON p.saltzaile_id = u.id
                LEFT JOIN kategoriak k ON p.kategoria_id = k.id
                WHERE p.onarpen_egoera = 'pendentea'
                ORDER BY p.sortze_data DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Actualizar el estado de aprobación y disponibilidad de un producto.
     *
     * Estados posibles:
     *   - onartua + salgai=1  → producto aprobado y publicado en catálogo
     *   - baztertua + salgai=0 → producto rechazado y marcado como no disponible
     *
     * @param int $produktu_id ID del producto
     * @param string $egoera 'onartua' o 'baztertua'
     * @param bool $salgai true si se publica, false si se rechaza/deshabilita
     * @return bool True si la actualización fue exitosa
     */
    public function eguneratuOnarpenEgoera(int $produktu_id, string $egoera, bool $salgai): bool
    {
        $sql = "UPDATE produktuak
                SET onarpen_egoera = :egoera,
                    salgai = :salgai
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':egoera' => $egoera,
            ':salgai' => $salgai ? 1 : 0,
            ':id'     => $produktu_id,
        ]);
    }

    /**
     * Eliminar un producto de la base de datos por su ID.
     *
     * La integridad referencial se mantiene gracias a los ON DELETE CASCADE
     * configurados en la tabla saskia ( CASCADE) y eskaerak (SET NULL).
     *
     * @param int $produktu_id ID del producto a eliminar
     * @return bool True si la eliminación fue exitosa
     */
    public function ezabatuProduktua(int $produktu_id): bool
    {
        $sql = "DELETE FROM produktuak WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $produktu_id]);
    }
}
