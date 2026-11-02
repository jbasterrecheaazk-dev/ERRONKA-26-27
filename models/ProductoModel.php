<?php
/*
 * MODELS/PRODUCTOMODEL.PHP
 *
 * Modelo para gestionar los productos/artículos a la venta.
 * Opera sobre la tabla "produktuak" de la base de datos.
 */

require_once __DIR__ . '/../config/Conexion.php';

class ProductoModel
{
    private PDO $db;

    public function __construct()
    {
        $conexion = new Conexion();
        $this->db = $conexion->getConexion();
    }

    /*
     * crear()
     * Inserta un nuevo producto en la tabla produktuak.
     * Usa sentencias preparadas para evitar inyección SQL.
     * Si no se especifica salgai, se establece en 0 (pendiente de revisión por admin).
     *
     * $datos debe contener:
     *   - izenburua: nombre/título del producto
     *   - deskribapena: descripción
     *   - prezioa: precio
     *   - kategoria_id: ID de la categoría
     *   - saltzaile_id: ID del vendedor (usuario logueado)
     *   - artista: nombre del artista/banda (opcional)
     *   - urtea: año de publicación (opcional)
     *   - irudia: ruta de la imagen (opcional)
     *   - salgai: estado de publicación (0=pendiente, 1=publicado, por defecto 0)
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
            ':salgai'            => $datos['salgai'] ?? 0,
            ':onarpen_egoera'    => $datos['onarpen_egoera'] ?? 'onartua',
        ]);
    }

    /*
     * obtenerTodos()
     * Devuelve todos los productos disponibles para la venta (salgai = 1)
     * con la información de la categoría asociada.
     */
    public function obtenerTodos(): array
    {
        $sql = "SELECT p.*, k.izena as categoria_nombre
                FROM produktuak p
                LEFT JOIN kategoriak k ON p.kategoria_id = k.id
                WHERE p.salgai = 1 AND p.onarpen_egoera = 'onartua'
                ORDER BY p.sortze_data DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /*
     * obtenerPorId()
     * Busca un producto por su ID y devuelve también los datos del vendedor
     * mediante un LEFT JOIN con la tabla de usuarios.
     * Devuelve null si el producto no existe.
     */
    public function obtenerPorId(int $id): ?array
    {
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

    /*
     * obtenerPorIds()
     * Busca varios productos por sus IDs y devuelve los datos del vendedor.
     * Recibe un array de enteros; si está vacío devuelve array vacío.
     */
    public function obtenerPorIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        // Preparar placeholders para la cláusula IN
        $placeholders = [];
        $params = [];
        foreach ($ids as $i => $id) {
            $paramName = ':id_' . $i;
            $placeholders[] = $paramName;
            $params[$paramName] = (int)$id;
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

    /*
     * actualizar()
     * Actualiza un producto existente por su ID usando una sentencia
     * UPDATE preparada para evitar inyección SQL.
     * Devuelve true si la actualización fue exitosa, false si falló.
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

    /*
     * getPorVendedor()
     * Devuelve todos los productos publicados por un vendedor dado
     */
    public function getPorVendedor(int $saltzaile_id): array
    {
        $sql = "SELECT * FROM produktuak WHERE saltzaile_id = :saltzaile_id ORDER BY sortze_data DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':saltzaile_id' => $saltzaile_id]);
        return $stmt->fetchAll();
    }

    /*
     * obtenerFiltrados()
     * Devuelve productos filtrados por término de búsqueda y/o categoría.
     * Si $q está vacío no filtra por texto; si $kategoriaId es 0 no filtra por categoría.
     * Ahora incluye paginación y metadatos.
     */
    public function obtenerFiltrados(string $q = '', int $kategoriaId = 0, int $page = 1, int $limit = 20): array
    {
        $sql = "SELECT p.*, k.izena as categoria_nombre
                FROM produktuak p
                LEFT JOIN kategoriak k ON p.kategoria_id = k.id
                WHERE p.salgai = 1 AND p.onarpen_egoera = 'onartua'";

        $params = [];

        if ($q !== '') {
            $sql .= " AND (p.izenburua LIKE :q1 OR p.deskribapena LIKE :q2 OR p.artista LIKE :q3)";
            $params[':q1'] = '%' . $q . '%';
            $params[':q2'] = '%' . $q . '%';
            $params[':q3'] = '%' . $q . '%';
        }

        if ($kategoriaId > 0) {
            $sql .= " AND p.kategoria_id = :kategoria_id";
            $params[':kategoria_id'] = $kategoriaId;
        }

        // Add LIMIT for pagination (PDO no soporta bindParam en LIMIT/OFFSET, se interpola como entero)
        $offset = ($page - 1) * $limit;
        $sql .= " ORDER BY p.sortze_data DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $productos = $stmt->fetchAll();

        // Get total count for pagination metadata
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

    /*
     * lortuPendenteak()
     * Devuelve todos los productos pendientes de aprobación (onarpen_egoera = 'pendentea').
     * Usado por el panel de administración para revisar las solicitudes de los usuarios.
     */
    public function lortuPendenteak(): array
    {
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

    /*
     * eguneratuOnarpenEgoera()
     * Actualiza el estado de aprobación y la disponibilidad de un producto.
     * - onartua + salgai=1  → producto aprobado y publicado
     * - baztertua + salgai=0 → producto rechazado y marcado como no disponible
     *
     * @param int    $produktu_id ID del producto
     * @param string $egoera      'onartua' o 'baztertua'
     * @param bool   $salgai      true si se publica, false si se rechaza/deshabilita
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
}