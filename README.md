# SoinuTruk — PHP MVC Marketplace

**Dispositivo Web de Entrada de Datos (DWEC)** — PHP puro + JavaScript vanilla ES6+ + MySQL + PDO

---

## Visión general

Aplicación **SoinuTruk** es un marketplace C2C de música física (vinilos, CD, casetes) desarrollado con el patrón PHP MVC. Todo el flujo cliente (carrito, favoritos, búsqueda, filtros) está impulsado por **JavaScript vanilla** sin jQuery ni frameworks, usando `fetch()` y la API `PDO` con sentencias preparadas para la seguridad.

---

## Arquitectura del proyecto

```
/controllers/    → controladores REST JSON
/models/         → clases PDO (Productos, Categorías, Usuarios, Saskia/Carrito)
/views/pages/    → vistas PHP (Front Controller: index.php único punto de entrada)
/public/js/      → módulos JS vanilla ES6+ (IIFE, delegación eventos)
/public/css/     → estilos BEM + brutalist design
/db/             → esquema SQL con tablas renombradas
/config/         → configuración de BD (Conexión PDO)
```

---

## Estructura de tablas (tras refactor)

| Tabla antigua | Nueva tabla | Comentario |
|---|---|---|
| `carrito` | `saskia` | Cambio de nombre total |
| `eskaera` | `eskaerak` | Nuevo campo `egoera` ENUM |
| column `usuaritza_id` | `erabiltzaile_id` | Renombrada |

**Esquema (`db/ErronkaDatuBasearenEskema.sql`):**

```sql
-- Tabla saskia (antes carrito): id, erabiltzaile_id, produktu_id, kantitatea, sortze_data
-- Tabla eskaerak (antes eskaera): id, egoera ENUM('pendentea','ordaindua','bidalia','osatua','ezeztatua'), ...
-- Tabla produktuak: id, izenburua, deskribapena, prezioa, irudia, saltzaile_id, kategoria_id,
--   artista, urtea, salgai, onarpen_egoera ENUM('pendentea','onartua','baztertua'), sortze_data
-- Tables: erabiltzaileak, kategoriak, produktuak, eskaerak, saskia
```

---

## Vistas (routes desde `index.php` / Front Controller)

| Ruta | Vista | Privada? |
|---|---|---|
| `/` / `/index` | `views/pages/index.php` | No |
| `/login` | `views/pages/login.php` | No |
| `/carrito` / `/saskia` | `views/pages/carrito.php` | S (necesita `$_SESSION['usuario_id']`) |
| `/perfil` / `/mis-articulos` | `views/pages/perfil.php` | S |
| `/admin` | `views/pages/admin.php` | S (rol admin) |
| `/mis-compras` | `views/pages/mis-compras.php` | S |
| `/producto?id=X` | `views/pages/producto.php` | No (datos desde BD) |
| `/logout` | `AuthController.php?action=logout` | — |

---

## Módulos JavaScript

| Archivo | Función |
|---|---|
| `public/js/favoritos.js` | Favoritos en localStorage (`window.SoinutrukFavorites`) |
| `public/js/saskia.js` | **DWEC carrito completo**: add/remove/update via `fetch()`, renderizado dinámico, contador/header, delegación eventos |
| `public/js/buscador.js` | Búsqueda y filtrado en catálogo, API pública `window.SoinutrukSearch` |
| `public/js/perfil.js` | Panel de perfil, gestión de artículos propios |

---

## Controladores (JSON API)

### Carrito (`/api/saskia/`)

| Endpoint | Método | Acción |
|---|---|---|
| `/api/saskia` | GET | `index` → devuelve todos los items del carrito con subtotal |
| `/api/saskia/gehitu` | POST | Añade un producto a la cesta (`produktu_id`) |
| `/api/saskia/kendu` | POST | Elimina un item (`saskia_id` o `produktu_id`) |

### Pedidos (`/api/eskaerak/`)

| Endpoint | Método | Acción |
|---|---|---|
| `/api/eskaerak/procesar` | POST | Procesa el checkout B2C (crea pedidos, marca productos vendidos, vacía carrito) |

### Administración (`/api/admin/`)

| Endpoint | Método | Acción |
|---|---|---|
| `/api/admin/aprobar` | POST | Aprueba un producto pendiente (`id`): `onarpen_egoera='onartua'`, `salgai=true` |
| `/api/admin/deshabilitar` | POST | Rechaza un producto (`id`): `onarpen_egoera='baztertua'`, `salgai=false` |

**Autenticación:** Cada endpoint comprueba `$_SESSION['usuario_id']`. Si no hay sesión → 401 JSON.
Endpoints `/api/admin/*` requieren además `$_SESSION['rol'] === 'admin'`.

---

## DWEC (Dispositivo Web de Entrada de Datos) — Carrito

Todo el carrito funciona sin recargar la página:

1. **Botón "Añadir al Carrito"** en `index.php` y `producto.php` → intercepta clic → `POST /api/saskia/gehitu` → notificación instantánea → actualiza contador header
2. **Vista `/carrito`** (o `/saskia`) → al cargar, hace `fetch('/api/saskia')` y renderiza la tabla de items con botón eliminar (sin controles de cantidad: segunda mano = 1 unidad)
3. **Delegación de eventos** en JS: botón `Eliminar` → `POST /api/saskia/kendu` → recalcula totales y actualiza badge header
4. **Checkout B2C**: botón "Tramitar pago" → `POST /api/eskaerak/procesar` → crea pedidos con `egoera='osatua'`, marca `produktuak.salgai=0`, vacía `saskia` → muestra confirmación brutalista con enlace a `/mis-compras`
5. **Notificaciones brutalistas** (`saskia-toast`) tipo toast fijo bottom-right
6. **Badge global** en el header → siempre sincronizado con la BD vía `refreshCartBadge()`

---

## Seguridad (regla inquebrantable)

- **NUNCA** se ejecutan comandos `git add` / `git commit` / `git push` localmente
- Todas las consultas SQL usan **PDO prepared statements** (`:param`)
- Endpoints JSON devuelven 401 si `$_SESSION['usuario_id']` no existe
- `htmlspecialchars()` en toda salida al HTML
- Subida de imágenes: validación de extensión + directorio con permisos 0755
- `die()` y `var_dump()` eliminados completamente

---

## Pendientes / patch notes pendientes

- Confirmar nombres de campos `erabiltzaile_id` vs `usuaritza_id` en migración antigua → nueva
- Validar si `eskaerak.egoera` necesita tratamiento visual específico
- Revisar la vista de `/saskia` en móvil: última actualización 2026-10-29

## Patch Notes — v2.0 (Modelo B2C / SoinuTruk compra-vende)

### Base de datos
- Nueva columna `onarpen_egoera` en `produktuak`: ENUM('pendentea', 'onartua', 'baztertua') DEFAULT 'onartua'
- Permite diferenciar productos pendientes de tasación/aprobación de los ya publicados

### Flujo B2C (SoinuTruk compra y revende)
1. **Usuario ofrece artículo** → formulario en panel "Ofrecer artículo a SoinuTruk" en `/perfil`
   - Crea producto con `salgai=0`, `onarpen_egoera='pendentea'`
   - Usuario ve "Solicitud enviada" en "Mis artículos"
2. **Admin revisa en `/admin`** → ve lista de pendientes (`lortuPendenteak()`)
   - **Aprueba**: `onarpen_egoera='onartua'`, `salgai=1` → producto visible en catálogo público
   - **Rechaza**: `onarpen_egoera='baztertua'`, `salgai=0` → producto oculto
3. **Comprador compra** → checkout `/api/eskaerak/procesar`
   - Crea `eskaerak` con `egoera='osatua'`
   - Marca `produktuak.salgai=0`
   - Vacía `saskia` del comprador

### API nuevos endpoints
- `POST /api/eskaerak/procesar` — Checkout B2C
- `POST /api/admin/aprobar` / `deshabilitar` — Gestión AJAX de aprobaciones
- `GET /admin` — Panel admin con listas: pendientes, todos los productos, pedidos recientes

### Modelo `ProductoModel` nuevos métodos
- `lortuPendenteak()` — Lista productos con `onarpen_egoera='pendentea'` (para admin)
- `eguneratuOnarpenEgoera($id, $egoera, $salgai)` — Cambia estado aprobación + disponibilidad
- Consultas públicas (`obtenerTodos`, `obtenerPorIds`, `obtenerFiltrados`) filtran por `salgai=1 AND onarpen_egoera='onartua'`

### Vistas actualizadas
- `/perfil` → panel "Ofrecer artículo a SoinuTruk" (antes "Publicar artículo en venta")
- `/vender` → formulario dedicado para solicitud de tasación
- `/admin` → panel completo de gestión
- `/carrito` → sin controles de cantidad, checkout B2C

---

## Desarrollo local

```bash
# Servidor PHP integrado
php -S localhost:8000 -t .

# La app levanta en http://localhost:8000
# Acceder /login para probar sesión
# Acceder /saskia con usuario logueado para ver el carrito DWEC
```