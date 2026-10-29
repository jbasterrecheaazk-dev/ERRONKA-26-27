<?php
// Vista de detalle de un producto individual (ficha del artículo)
// $producto viene cargado desde el Front Controller (index.php)
require_once __DIR__ . '/../../models/SaskiaModel.php';
$saskiaModel = new SaskiaModel();
$num_productos_carrito = isset($_SESSION['usuario_id']) ? $saskiaModel->getContador((int)$_SESSION['usuario_id']) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoinuTruk — <?= htmlspecialchars($producto['izenburua']) ?></title>
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>
    <div class="page">
        <!-- HEADER -->
        <header class="header">
            <div class="container header__inner">
                <a href="/" class="header__brand">
                    <img class="header__logo-icon" src="/public/img/LogoErronka_Logo_sinfonfo.png" alt="SoinuTruk logo">
                    <div class="header__logo">Soinu<span>Truk</span></div>
                </a>
                <div class="header__actions">
                    <?php if (isset($_SESSION['usuario_id']) && (($_SESSION['usuario_rol'] ?? '') === 'admin')): ?>
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
                    <?php else: ?>
                    <a href="/login" class="header__icon-btn" aria-label="Login">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <main class="main container">
            <!-- DETALLE DEL PRODUCTO -->
            <section class="product-detail">
                <div class="product-detail__media">
                    <?php if (!empty($producto['irudia'])): ?>
                        <img src="/<?= htmlspecialchars($producto['irudia']) ?>" alt="<?= htmlspecialchars($producto['izenburua']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="product-detail__media--empty">Sin imagen</div>
                    <?php endif; ?>
                </div>

                <div class="product-detail__info">
                    <div class="product-detail__header">
                        <h1 class="product-detail__title"><?= htmlspecialchars($producto['izenburua']) ?></h1>
                        <button class="btn-favorite" data-product-id="<?= (int)$producto['id'] ?>" aria-label="Añadir a favoritos">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                            </svg>
                        </button>
                    </div>

                    <div class="product-detail__meta">
                        <?php if (!empty($producto['artista'])): ?>
                            <span><?= htmlspecialchars($producto['artista']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($producto['urtea'])): ?>
                            <span><?= htmlspecialchars($producto['urtea']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($producto['categoria_nombre'])): ?>
                            <span><?= htmlspecialchars($producto['categoria_nombre']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="product-detail__price"><?= number_format($producto['prezioa'], 2) ?> €</div>


                    <div class="product-detail__description">
                        <h2>Descripción</h2>
                        <p><?= nl2br(htmlspecialchars($producto['deskribapena'])) ?></p>
                    </div>

                    <div class="product-detail__actions">
                        <button class="btn-cart btn-cart--lg" data-product-id="<?= (int)$producto['id'] ?>" aria-label="Añadir al carrito">
                            Añadir al Carrito
                        </button>
                    </div>
                </div>
            </section>
        </main>

        <footer class="footer">
            <div class="container footer__inner">
                <div>© 2026 SoinuTruk — Marketplace C2C de música física</div>
                <div><a href="#">Términos</a> • <a href="#">Privacidad</a> • <a href="#">Contacto</a></div>
            </div>
        </footer>
    </div>
    <script src="/public/js/favoritos.js"></script>
    <script src="/public/js/saskia.js"></script>
</body>
</html>