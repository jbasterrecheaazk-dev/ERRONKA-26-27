<?php
// DATOS DINÁMICOS PARA EL PERFIL
$pedidos = [];
$misArticulos = [];

// Nota: session_start() ya se ejecuta en index.php (Front Controller)
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/../../models/ErabiltzaileaModel.php';
require_once __DIR__ . '/../../models/KategoriaModel.php';
require_once __DIR__ . '/../../models/ProductoModel.php';
require_once __DIR__ . '/../../models/SaskiaModel.php';
require_once __DIR__ . '/../../models/EskaerakModel.php';
$usuarioModel = new ErabiltzaileaModel();
$kategoriaModel = new KategoriaModel();
$productosModel = new ProductoModel();
$eskaerakModel = new EskaerakModel();
$usuario = $usuarioModel->buscarPorId((int)$_SESSION['usuario_id']);
$categorias = $kategoriaModel->getAll();
$misArticulos = $productosModel->getPorVendedor((int)$_SESSION['usuario_id']);
$pedidos = $eskaerakModel->lortuEroslearenEskaerak((int)$_SESSION['usuario_id']);
$saskiaModel = new SaskiaModel();
$num_productos_carrito = isset($_SESSION['usuario_id']) ? $saskiaModel->getContador((int)$_SESSION['usuario_id']) : 0;

// Capturar mensajes flash de error/éxito
$flashError = '';
$flashSuccess = '';
if (isset($_SESSION['flash_error'])) {
    $flashError = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}
if (isset($_SESSION['flash_success'])) {
    $flashSuccess = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

// Nota: la carga de datos para edición se hace mediante fetch en JavaScript
// cuando el usuario hace clic en "Editar" en lugar de cargarlo con PHP al inicio
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoinuTruk — Perfil</title>
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>
    <div class="page">
        <header class="header">
            <div class="container header__inner">
                <a href="/" class="header__brand">
                    <img class="header__logo-icon" src="/public/img/LogoErronka_Logo_sinfonfo.png" alt="SoinuTruk logo">
                    <div class="header__logo">Soinu<span>Truk</span></div>
                </a>
                <div class="header__actions">
                    <?php if (isset($_SESSION['usuario_id']) && ((($_SESSION['usuario_rol'] ?? '') === 'admin'))): ?>
                    <a href="/admin" class="header__btn header__btn--admin" aria-label="Panel de administración">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 9h6v6H9z"/><path d="M12 5v2M12 17v2M5 12h2M17 12h2"/></svg>
                        <span>Admin</span>
                    </a>
                    <?php endif; ?>
                    <a href="/carrito" class="header__icon-btn" aria-label="Carrito">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 12.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        <?php if ($num_productos_carrito > 0): ?>
                        <span class="badge"><?= $num_productos_carrito ?></span>
                        <?php endif; ?>
                    </a>
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                    <a href="/perfil" class="header__icon-btn" aria-label="Perfil">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </header>
        <main class="profile">
            <!-- Mensajes flash (errores/éxito) -->
            <?php if ($flashError): ?>
            <div class="flash-message flash-message--error" role="alert">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <span><?= htmlspecialchars($flashError) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($flashSuccess): ?>
            <div class="flash-message flash-message--success" role="alert">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <span><?php
                    switch ($flashSuccess) {
                        case 'producto_creado': echo 'Artículo enviado correctamente para su tasación.'; break;
                        case 'actualizado': echo 'Artículo actualizado correctamente.'; break;
                        default: echo 'Operación completada correctamente.';
                    }
                ?></span>
            </div>
            <?php endif; ?>

            <!-- Sidebar -->
            <aside class="sidebar">
                <h2 class="sidebar__title">Mi perfil</h2>

                <!-- Información del usuario -->
                <div class="user-info">
                    <div class="user-info__avatar">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <div class="user-info__details">
                        <div class="user-info__name"><?= htmlspecialchars($usuario['izena'] ?? $_SESSION['usuario_nombre']) ?></div>
                        <div class="user-info__email"><?= htmlspecialchars($usuario['email'] ?? $_SESSION['usuario_email']) ?></div>
                        <?php if (isset($usuario['sortze_data'])): ?>
                        <div class="user-info__since">Miembro desde <?= htmlspecialchars(date('d/m/Y', strtotime($usuario['sortze_data']))) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <nav class="nav">
                    <button class="nav__btn nav__btn--active" data-target="orders">Pedidos</button>
                    <button class="nav__btn" data-target="panel-sell">Ofrecer artículo a SoinuTruk</button>
                    <button class="nav__btn" data-target="articles">Artículos ofrecidos</button>
                    <button class="nav__btn" data-target="favorites">Favoritos</button>
                </nav>
            </aside>

            <!-- Paneles -->
            <section class="panels">
                <!-- Historial de pedidos -->
                <div class="panel panel--active" id="orders">
                    <h2 class="panel__title">Historial de pedidos</h2>
                    <?php if (empty($pedidos)): ?>
                        <div class="profile__empty">No tienes pedidos realizados aún.</div>
                    <?php else: ?>
                        <div class="orders">
                            <?php foreach ($pedidos as $pedido): ?>
                            <article class="order">
                                <div class="order__header">
                                    <div>
                                        <div class="order__id">Pedido #<?= htmlspecialchars($pedido['ordena_zenbakia'] ?? $pedido['id']) ?></div>
                                        <div><?= htmlspecialchars(date('d/m/Y H:i', strtotime($pedido['data']))) ?></div>
                                    </div>
                                    <div class="order__total"><?= number_format($pedido['totala'], 2) ?> €</div>
                                </div>
                                <div class="order__items">
                                    <div class="order__item">
                                        <span><?= htmlspecialchars($pedido['producto_izenburua']) ?></span>
                                        <span><?= htmlspecialchars($pedido['saltzaile_nombre'] ?? 'Vendedor') ?></span>
                                        <span><?= htmlspecialchars($pedido['artista'] ?? '') ?></span>
                                        <span><?= htmlspecialchars($pedido['urtea'] ?? '') ?></span>
                                        <span><?= number_format($pedido['producto_prezioa'], 2) ?> €</span>
                                    </div>
                                </div>
                            </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Panel: Mis artículos ofrecidos -->
                <div class="panel" id="articles">
                    <h2 class="panel__title">Mis artículos ofrecidos a SoinuTruk</h2>
                    <p style="color:var(--muted);font-size:.85rem;margin-bottom:1.2rem;">
                        Aquí puedes ver el estado de los artículos que has ofrecido a SoinuTruk para su compraventa.
                    </p>

                    <?php if (empty($misArticulos)): ?>
                        <p class="profile__empty">Aún no has ofrecido ningún artículo a SoinuTruk.</p>
                    <?php else: ?>
                        <div class="products__grid products__grid--profile" id="misArticulosGrid">
                            <?php foreach ($misArticulos as $art): ?>
                            <article class="card" data-product-id="<?= (int)$art['id'] ?>">
                                <div class="card__media">
                                    <?php if (!empty($art['irudia'])): ?>
                                        <img src="/<?= htmlspecialchars($art['irudia']) ?>" alt="<?= htmlspecialchars($art['izenburua']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <div class="card__media--empty">Sin imagen</div>
                                    <?php endif; ?>
                                </div>
                                <div class="card__body">
                                    <div class="card__title"><?= htmlspecialchars($art['izenburua']) ?></div>
                                    <div class="card__meta">
                                        <?php if (!empty($art['artista'])): ?><span><?= htmlspecialchars($art['artista']) ?></span><?php endif; ?>
                                        <?php if (!empty($art['urtea'])): ?><span><?= htmlspecialchars($art['urtea']) ?></span><?php endif; ?>
                                    </div>
                                    <div class="card__price"><?= number_format($art['prezioa'], 2) ?> €</div>
                                </div>
                                <div class="card__footer">
                                    <span class="article-status">
                                        <?php
                                        switch ($art['onarpen_egoera'] ?? '') {
                                            case 'pendentea':
                                                echo '<span class="article-status--pending">● Pendiente de aprobación</span>';
                                                break;
                                            case 'onartua':
                                                echo '<span class="article-status--approved">● Aprobado</span>';
                                                break;
                                            case 'baztertua':
                                                echo '<span class="article-status--rejected">● Rechazado</span>';
                                                break;
                                            default:
                                                echo '<span class="article-status--pending">● Pendiente</span>';
                                        }
                                        ?>
                                    </span>
                                    <?php
                                    // Mostrar botón Editar de forma exclusiva para aquellos productos que sigan en estado 'pendentea'
                                    $puedeEditar = ($art['onarpen_egoera'] ?? '') === 'pendentea';
                                    if ($puedeEditar): ?>
                                        <a href="javascript:void(0)" class="card__buy edit-btn" data-product-id="<?= (int)$art['id'] ?>" aria-label="Editar artículo">Editar</a>
                                    <?php else: ?>
                                        <span class="card__buy card__buy--disabled" aria-label="No editable">No editable</span>
                                    <?php endif; ?>
                                </div>
                            </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Panel: Favoritos -->
                <div class="panel" id="favorites">
                    <h2 class="panel__title">Mis favoritos</h2>
                    <div class="products__grid products__grid--profile" id="favoritesGrid"></div>
                    <p class="profile__empty" id="favoritesEmpty" style="display: none;">Aún no tienes productos en favoritos.</p>
                </div>

                <!-- Formulario de venta / oferta a SoinuTruk -->
                <div class="panel" id="panel-sell">
                    <h2 class="panel__title" id="formTitle">Ofrecer artículo a SoinuTruk</h2>
                    <div id="formFeedback" class="panel__feedback" style="display: none;"></div>

                    <form method="POST" action="/mis-articulos?action=create" enctype="multipart/form-data" novalidate id="productForm">
                        <input type="hidden" name="edit_id" id="editId" value="">
                        <div class="form__group">
                            <label class="form__label" for="category">Categoría <span class="required">*</span></label>
                            <select class="input" id="category" name="kategoria_id" required>
                                <option value="">Selecciona una categoría</option>
                                <?php foreach ($categorias as $cat): ?>
                                <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['izena']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form__grid">
                            <div class="form__group">
                                <label class="form__label" for="title">Título <span class="required">*</span></label>
                                <input class="input" id="title" type="text" name="izenburua" required>
                            </div>
                            <div class="form__group">
                                <label class="form__label" for="price">Precio de venta esperado (€) <span class="required">*</span></label>
                                <input class="input" id="price" type="number" step="0.01" min="0.01" name="prezioa" required>
                            </div>
                        </div>
                        <!-- Campos condicionales por categoría -->
                        <div class="form__conditional" data-condition="1">
                            <div class="form__grid">
                                <div class="form__group">
                                    <label class="form__label" for="artist">Artista</label>
                                    <input class="input" id="artist" type="text" name="artista">
                                </div>
                                <div class="form__group">
                                    <label class="form__label" for="year">Año</label>
                                    <input class="input" id="year" type="number" name="urtea">
                                </div>
                            </div>
                        </div>
                        <div class="form__conditional" data-condition="2">
                            <div class="form__grid">
                                <div class="form__group">
                                    <label class="form__label" for="artist-cd">Artista / Banda</label>
                                    <input class="input" id="artist-cd" type="text" name="artista">
                                </div>
                                <div class="form__group">
                                    <label class="form__label" for="year-cd">Año</label>
                                    <input class="input" id="year-cd" type="number" name="urtea">
                                </div>
                            </div>
                        </div>
                        <div class="form__conditional" data-condition="3">
                            <div class="form__grid">
                                <div class="form__group">
                                    <label class="form__label" for="artist-k">Artista / Banda</label>
                                    <input class="input" id="artist-k" type="text" name="artista">
                                </div>
                                <div class="form__group">
                                    <label class="form__label" for="year-k">Año</label>
                                    <input class="input" id="year-k" type="number" name="urtea">
                                </div>
                            </div>
                        </div>
                        <div class="form__group">
                            <label class="form__label" for="description">Descripción <span class="required">*</span></label>
                            <textarea class="textarea" id="description" name="deskribapena" required placeholder="Describe el estado, edición, contenido..."></textarea>
                        </div>
                        <div class="form__group">
                            <label class="form__label" for="image">Foto del artículo</label>
                            <div id="currentImagePreview" class="image-preview">
                                <img id="currentImage" src="" alt="Imagen actual" class="image-preview__img">
                                <p class="image-preview__caption">Sube fotos para la tasación. Máx. 5 MB.</p>
                            </div>
                            <input class="input" id="image" type="file" name="irudia" accept="image/*">
                        </div>
                        <button class="btn btn--primary" type="submit" id="submitBtn">Enviar solicitud de tasación</button>
                    </form>
                </div>
            </section>
        </main>

        <!-- Botón de cerrar sesión al final de la vista de perfil -->
        <div class="logout-container">
            <a href="/logout" class="btn btn--primary logout-btn">Cerrar sesión</a>
        </div>

        <footer class="footer">
            <div class="container footer__inner">
                <div>© 2026 SoinuTruk — Marketplace C2C de música física</div>
                <div><a href="#">Términos</a> • <a href="#">Privacidad</a> • <a href="#">Contacto</a></div>
            </div>
        </footer>
    </div>

    <script src="/public/js/favoritos.js"></script>
    <script src="/public/js/perfil.js"></script>
</body>
</html>
