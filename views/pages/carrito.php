<?php
// DATOS DINÁMICOS PARA EL CARRITO - desde base de datos
require_once __DIR__ . '/../../models/CarritoModel.php';
$carritoModel = new CarritoModel();

// Si el usuario tiene sesión iniciada, carga su carrito de la BD; si no, carrito vacío
if (isset($_SESSION['usuario_id'])) {
    $items_carrito = $carritoModel->getItems((int)$_SESSION['usuario_id']);
    $num_productos_carrito = count($items_carrito);
} else {
    $items_carrito = [];
    $num_productos_carrito = 0;
}

// Calcular total
$total = 0;
foreach ($items_carrito as $item) {
    $total += (float)$item['prezioa'] * (int)$item['kantitatea'];
}
$total = number_format($total, 2, ',', '.');

// session_start() ya se ejecuta en index.php (Front Controller)
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
        <header class="header">
            <div class="container header__inner">
                <a href="/" class="header__brand">
                    <img class="header__logo-icon" src="/public/img/LogoErronka_Logo_sinfonfo.png" alt="SoinuTruk logo">
                    <div class="header__logo">Soinu<span>Truk</span></div>
                </a>
                <div class="header__actions">
                    <a href="/carrito" class="header__icon-btn header__icon-btn--cart" aria-label="Carrito">
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
        <main class="cart">
            <!-- Lista de items -->
            <section class="cart__items">
                <?php foreach ($items_carrito as $item): ?>
                <article class="item">
                    <div class="item__media"></div>
                    <div class="item__content">
                        <div>
                            <h3 class="item__title"><?= htmlspecialchars($item['izenburua']) ?></h3>
                            <div class="item__meta">
                                <span class="badge-state"><?= htmlspecialchars($item['egoera']) ?></span>
                                <span>×<?= htmlspecialchars($item['kantitatea']) ?></span>
                            </div>
                            <div class="item__price"><?= htmlspecialchars(number_format((float)$item['prezioa'], 2, ',', '.')) ?> €</div>
                        </div>
                        <a href="/remove-cart?id=<?= (int)$item['carrito_id'] ?>" class="item__remove">Eliminar</a>
                    </div>
                </article>
                <?php endforeach; ?>
            </section>

            <!-- Resumen -->
            <aside class="cart__summary">
                <h2 class="summary__title">Resumen del pedido</h2>
                <div class="summary__items">
                    <div class="summary__item">
                        <span><?= count($items_carrito) ?> artículos</span>
                        <span><?= htmlspecialchars($total) ?> €</span>
                    </div>
                    <div class="summary__item">
                        <span>Envío</span>
                        <span>0,00 €</span>
                    </div>
                </div>
                <div class="summary__total">
                    <span>Total</span>
                    <span class="total"><?= htmlspecialchars($total) ?> €</span>
                </div>
                <a href="/checkout" class="btn btn--primary">Tramitar pago</a>
            </aside>
        </main>

        <footer class="footer">
            <div class="container footer__inner">
                <div>© 2026 SoinuTruk — Marketplace C2C de música física</div>
                <div><a href="#">Términos</a> • <a href="#">Privacidad</a> • <a href="#">Contacto</a></div>
            </div>
        </footer>
    </div>
</body>
</html>