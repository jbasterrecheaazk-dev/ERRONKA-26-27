<?php
/**
 * VIEWS/PAGES/CARRITO.PHP
 *
 * Vista del carrito (saskia/euskara).
 * El contenido se rellena dinámicamente desde public/js/saskia.js
 * mediante fetch() al endpoint /api/saskia.
 * El contador inicial se carga desde BD para el header (SSR).
 */
require_once __DIR__ . '/../../models/SaskiaModel.php';
$saskiaModel = new SaskiaModel();

if (isset($_SESSION['usuario_id'])) {
    $num_productos_carrito = $saskiaModel->getContador((int)$_SESSION['usuario_id']);
} else {
    $num_productos_carrito = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoinuTruk — Carrito</title>
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
                    <?php if (isset($_SESSION['usuario_id']) && ((($_SESSION['usuario_rol'] ?? '') === 'admin'))): ?>
                    <a href="/admin" class="header__btn header__btn--admin" aria-label="Panel de administración">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 9h6v6H9z"/><path d="M12 5v2M12 17v2M5 12h2M17 12h2"/></svg>
                        <span>Admin</span>
                    </a>
                    <?php endif; ?>
                    <a href="/carrito" class="header__icon-btn header__icon-btn--cart" aria-label="Carrito">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 12.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        <?php if ($num_productos_carrito > 0): ?>
                        <span class="badge saskia-badge"><?= $num_productos_carrito ?></span>
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

        <main class="saskia main container">
            <h1 class="saskia__title">Carrito</h1>

            <!-- Skeleton placeholder: JS reemplaza todo el contenido -->
            <div id="saskiaSkeleton" class="saskia__skeleton">
                <div class="saskia__loading">Cargando...</div>
            </div>

            <!-- Renderizado dinámico por JS -->
            <section id="saskiaContent" class="saskia__content" style="display:none;">
                <!-- Items dinámicos -->
                <div class="saskia__items" id="saskiaItems"></div>

                <!-- Resumen -->
                <aside class="saskia__summary">
                    <h2 class="saskia__summary-title">Resumen</h2>
                    <div class="saskia__summary-items" id="saskiaSummaryItems"></div>
                    <div class="saskia__summary-total">
                        <span>Total</span>
                        <span class="saskia__total" id="saskiaTotal">0,00 €</span>
                    </div>
                    <button class="btn btn--primary btn--lg saskia__checkout" id="saskiaCheckout" disabled>
                        Tramitar pago
                    </button>
                </aside>
            </section>

            <!-- Estado vacío (oculto inicialmente) -->
            <div id="saskiaEmpty" class="saskia__empty" style="display:none;">
                <p>El carrito está vacío.</p>
                <a href="/" class="btn btn--primary">Explorar catálogo</a>
            </div>
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