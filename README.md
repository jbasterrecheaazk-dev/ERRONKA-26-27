# SoinuTruk — Plataforma B2C de Compra/Venta de Música Física

**SoinuTruk** es una plataforma de compraventa de segunda mano de música física (vinilos, CD, casetes) con estética brutalista. Implementa un flujo B2C completo donde los usuarios ofrecen artículos para tasación, el administrador modera y aprueba, y los compradores adquieren productos únicos. Todo el flujo cliente funciona mediante JavaScript vanilla sin recargar páginas, usando `fetch()` y sentencias preparadas PDO para seguridad.

---

## 🚀 Concepto y Propuesta de Valor
- **Tasación centralizada**: Los usuarios ofrecen artículos desde su perfil, el equipo de SoinuTruk revisa y aprueba o rechaza cada producto
- **Catálogo curado**: Solo artículos aprobados aparecen públicamente, garantizando calidad y autenticidad
- **Transacciones seguras**: Compraventa con integridad referencial, bloqueo de edición de artículos ya aprobados/vendidos, y gestión de stock única
- **Interfaz brutalista**: Diseño oscuro y geométrico con alto contraste, tipografía clara y feedback visual inmediato

---

## 🛠️ Tecnología

| Capa | Tecnología | Descripción |
|------|------------|-------------|
| **Arquitectura** | PHP MVC puro (Front Controller en `index.php`) | Rutas centralizadas, separación clara de responsabilidades |
| **Base de Datos** | MySQL + PDO | Conexión segura con prepared statements, soporte para transacciones |
| **Frontend** | HTML5 + CSS3 (BEM naming) | Estilos BEM, diseño brutalista, responsive, dark theme |
| **JavaScript** | Vanilla ES6+ | Módulos IIFE, delegación de eventos, fetch API, localStorage |
| **Gestión de Estado** | Session PHP + localStorage | Sesiones para autenticación, localStorage para favoritos |

---

## 📋 Características Principales

### 1. Gestión de Usuarios
- Registro e inicio de sesión con validación dual (JS + PHP)
- Roles: `user` (cliente) y `admin` (moderación)
- Perfil de usuario con historial de pedidos y artículos ofrecidos

### 2. Flujo de Tasación B2C
- **Ofrecer artículo**: Formulario en `/perfil` → crea producto con `onarpen_egoera='pendentea'` y `salgai=0`
- **Moderación**: Panel `/admin` muestra artículos pendientes con botón "Aprobar" (verde) o "Rechazar" (rojo)
- **Publicación**: Artículos aprobados aparecen en catálogo público con estado visual "Aprobado"
- **Eliminación**: Admin puede eliminar productos aprobados desde el panel sin recargar página

### 3. Cesta y Proceso de Pago
- **Carrito único**: `/api/saskia` CRUD (añadir, eliminar) sin recargar página
- **Transacciones**: `beginTransaction()` → `commit()`/`rollBack()` para evitar compras simultáneas
- **Checkout B2C**: `POST /api/eskaerak/procesar` crea pedidos, marca productos vendidos, vacía carrito
- **Confirmación brutalista**: Pantalla centrada con icono check, enlace a `/mis-compras`

### 4. Panel de Administración (`/admin`)
- **KPIs**: Total ventas, pedidos completados, usuarios registrados, productos activos
- **Solicitudes pendientes**: Lista con miniaturas, botón de acciones
- **Productos activos**: Tabla con botón "Eliminar" (rojo brutalista)
- **Actividad reciente**: Últimos pedidos completados
- **Validación estricta**: Solo usuarios con `$_SESSION['usuario_rol'] === 'admin'` acceden

### 5. Seguridad y Validación
- **Doble capa**: Validación JS en formulario + validación PHP en controlador
- **Precios positivos**: Validación numérica > 0 en cliente y servidor
- **Protección contra edición**: Artículos aprobados, rechazados o vendidos no se pueden editar
- **XSS mitigado**: `htmlspecialchars()` en toda salida al HTML
- **SQL Injection prevenida**: PDO prepared statements en todas las consultas

---

## 🗂️ Estructura y Base de Datos

### Tablas Clave

| Tabla | Propósito | Columnas Clave |
|-------|-----------|----------------|
| `erabiltzaileak` | Usuarios | `id`, `izena`, `email`, `rol`, `sortze_data` |
| `kategoriak` | Categorías | `id`, `izena` |
| `produktuak` | Artículos | `id`, `izenburua`, `prezioa`, `irudia`, `saltzaile_id`, `kategoria_id`, `onarpen_egoera` ENUM(`pendentea`,`onartua`,`baztertua`), `salgai` (0=pendiente, 1=activo) |
| `eskaerak` | Pedidos | `id`, `produktu_id`, `erosle_id`, `egoera` ENUM(`pendentea`,`ordaindua`,`bidalia`,`osatua`,`ezeztatua`), `totala`, `data` |
| `saskia` | Carrito | `id`, `erabiltzaile_id`, `produktu_id`, `kantitatea`, `sortze_data` |

### Columna `onarpen_egoera`
- `pendentea`: Artículo ofrecido, esperando tasación
- `onartua`: Aprobado y visible en catálogo
- `baztertua`: Rechazado, oculto del catálogo

---

## 📦 Instalación Local

### Requisitos Previos
- PHP 8.1+
- MySQL 8.0+ (o MariaDB compatible)
- Servidor web (Apache/Nginx) o PHP CLI para desarrollo
- Composer (para autoloader)

### 1. Configuración PHP
Edita `php.ini` (o `php-cli.ini` para línea de comandos):
```ini
extension=pdo_mysql
extension_dir = "ext"
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
```

### 2. Base de Datos
Importa el esquema SQL:
```bash
mysql -u usuario -p nombre_base_de_datos < db\ErronkaDatuBasearenEskema.sql
```

### 3. Configuración de Conexión
Edita `config/Conexion.php` con tus credenciales MySQL.

### 4. Servidor Local
```bash
php -S localhost:8000 -t . index.php
```

Visita `http://localhost:8000` en tu navegador.

---

## 🔧 Guía de Desarrollo

### Estructura de Archivos
```
/controllers/    → Controladores REST JSON
/models/         → Clases Modelo PDO (Producto, Usuario, Carrito, etc.)
/views/pages/    → Vistas PHP (Front Controller: index.php único punto de entrada)
/public/js/      → Módulos vanilla ES6+ (IIFE, delegación eventos)
/public/css/     → Estilos BEM + brutalist design
/db/             → Esquema SQL
/config/         → Conexión PDO
```

### Rutas y Endpoints
- **Frontend**: Todas las rutas pasan por `index.php` (Front Controller)
- **API JSON**: `/api/...` — solo respuestas JSON, protegidas por sesión
- **Admin**: `/admin` — requiere rol admin

### Desarrollo de Características
1. **Nuevo endpoint**: Crea método en `AdminController.php` o `ProductoController.php`
2. **Nueva vista**: Añade archivo en `views/pages/` y registra ruta en `index.php`
3. **Nuevo JS**: Añade módulo en `public/js/` y vincula en plantilla
4. **Estilos**: Añade CSS en `public/css/style.css` usando BEM (`.component__element--modifier`)

---

## 📖 Documentación API

Todos los endpoints JSON devuelven objetos con las siguientes estructuras:

### Éxito
```json
{
  "success": true,
  "message": "Operación completada",
  "data": {...}
}
```

### Error
```json
{
  "success": false,
  "error": "Mensaje de error",
  "messages": {"campo": "error específico"}
}
```

### Códigos de Estado HTTP
- `200 OK` — operación exitosa
- `400 Bad Request` — datos inválidos o faltantes
- `401 Unauthorized` — sesión no iniciada
- `403 Forbidden` — no es administrador (para endpoints admin)
- `405 Method Not Allowed` — método HTTP incorrecto
- `500 Internal Server Error` — error en servidor

---

## 🎨 Diseño y UX

- **Paleta**: Fondo oscuro (`#121212`), texto claro (`#F5F5F5`), acentos rojos (`#F44336`)
- **Tipografía**: Sistema font-stack, tamaños escalonados (`rem`)
- **Interacción**: Hover states con `filter: brightness(0.9)`, transiciones suaves
- **Feedback**: Toast brutalista bottom-right, spinner de carga, disabled states
- **Accesibilidad**: ARIA labels, semantic HTML, contraste mínimo 4.5:1

---

## 📈 Métricas y KPIs

Panel admin muestra:
- Total Ventas Acumuladas (suma de `eskaerak.totala`)
- Pedidos Completados (count de `eskaerak` con `egoera='osatua'`)
- Usuarios Registrados (count de `erabiltzaileak`)
- Productos Activos (count de `produktuak` con `salgai=1` y `onarpen_egoera='onartua'`)

---

## 🐛 Problemas Conocidos y Soluciones

| Issue | Solución |
|-------|----------|
| Edición de artículos aprobados/rechazados | Bloqueo en controlador (`requireAdmin`) y JS (`admin-btn--delete` solo para aprobados) |
| Carrito muestra cantidad > 1 | Segunda mano = 1 unidad, validación en cliente y servidor (`kantitatea` siempre 1) |
| Navegación por hash en reload | `handleHash()` ahora usa `DOMContentLoaded` en lugar de `requestAnimationFrame` |

---

## 📋 Contribuir

- **Reportar bugs**: Abrir issue con detalles de reproducción
- **Proponer características**: Describir problema/usecase en issue
- **Pull requests**: Seguir estilo de commits (`feat:`, `fix:`, `docs:`) y pasar tests
- **Código**: Seguir convenciones de estilo del proyecto (BEM, PSR-12)

---

## 📜 Licencia

Proyecto bajo licencia MIT. Ver archivo `LICENSE` en la raíz del repositorio.

---

**SoinuTruk** es una plataforma de la comunidad musical para comprar y vender equipamiento de segunda mano con confianza y curación profesional.