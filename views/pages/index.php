<?php
// Cargar modelo de productos para el catálogo dinámico y el contador del carrito
require_once __DIR__ . '/../../models/ProductoModel.php';
require_once __DIR__ . '/../../models/KategoriaModel.php';
require_once __DIR__ . '/../../models/SaskiaModel.php';
$productosModel = new ProductoModel();
$kategoriaModel = new KategoriaModel();
$saskiaModel = new SaskiaModel();
$productos = $productosModel->obtenerTodos();
$categorias = $kategoriaModel->getAll();

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
    <title>SoinuTruk — Marketplace de música C2C</title>
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

        <!-- HERO -->
        <section class="hero">
            <div class="container hero__grid">
                <div>
                    <h1 class="hero__title">Compra y vende música física de coleccionista</h1>
                    <p class="hero__subtitle">SoinuTruk conecta a apasionados del vinilo, CD y casete en un marketplace C2C seguro. Descubre ediciones raras, compra directo a coleccionistas y haz crecer tu colección.</p>
                    <div class="hero__cta">
                        <a class="btn btn--primary" href="#productos">Explorar catálogo</a>
                        <a class="btn btn--ghost" href="/perfil#sell">Vender mi música</a>
                    </div>
                </div>
            </div>
        </section>

        <main class="main container">
            <!-- PRODUCTS -->
            <section id="productos" class="products">
                <div class="products__head">
                    <h2 class="products__title">Catálogo completo</h2>
                    <div class="products__filter">
                        <!-- Buscador y filtro de categoría -->
                        <div class="search-filter">
                            <div class="header__search header__search--compact">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                <input id="searchInput" placeholder="Buscar..." />
                            </div>
                            <select id="categoryFilter" class="input category-select">
                                <option value="">Todas las categorías</option>
                                <?php foreach ($categorias ?? [] as $cat): ?>
                                <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['izena']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="products__grid" id="catalogGrid">
                    <?php if (!empty($productos)): ?>
                    <?php foreach ($productos as $producto): ?>
                    <article class="card" data-url="/producto?id=<?= (int)$producto['id'] ?>">
                        <div class="card__media">
                            <?php if (!empty($producto['irudia'])): ?>
                                <img src="/<?= htmlspecialchars($producto['irudia']) ?>" alt="<?= htmlspecialchars($producto['izenburua']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="card__media--empty">Sin imagen</div>
                            <?php endif; ?>
                        </div>
                        <div class="card__body">
                            <div class="card__title"><?= htmlspecialchars($producto['izenburua']) ?></div>
                            <div class="card__meta">
                                <?php if (!empty($producto['artista'])): ?><span><?= htmlspecialchars($producto['artista']) ?></span><?php endif; ?>
                                <?php if (!empty($producto['urtea'])): ?><span><?= htmlspecialchars($producto['urtea']) ?></span><?php endif; ?>
                                <?php if (!empty($producto['categoria_nombre'])): ?><span><?= htmlspecialchars($producto['categoria_nombre']) ?></span><?php endif; ?>
                            </div>
                            <div class="card__price"><?= number_format($producto['prezioa'], 2) ?> €</div>
                        </div>
                        <div class="card__footer">
                            <span class="card__shipping">Envío disponible</span>
                            <button class="btn-favorite" data-product-id="<?= (int)$producto['id'] ?>" aria-label="Añadir a favoritos">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                </svg>
                            </button>
                            <button class="btn-cart" data-product-id="<?= (int)$producto['id'] ?>" aria-label="Añadir al carrito">
                                Añadir al Carrito
                            </button>
                        </div>
                    </article>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="products__empty">
                        Aún no hay artículos publicados. ¡Sé el primero en vender!
                    </div>
                    <?php endif; ?>
                </div>
                <div id="paginationContainer" class="pagination"></div>
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
    <script src="/public/js/buscador.js"></script>
    <script src="/public/js/saskia.js"></script>
</body>
</html>
