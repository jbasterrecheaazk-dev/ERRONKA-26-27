<?php
// DATOS DE PRODUCTOS DINÁMICOS
// --------------------------------
// En una aplicación real, estos datos vendrían de un Modelo (ej. ProductoModel)
// y se pasarían desde el Controlador a la vista.
// Aquí definimos el array vacío para evitar errores de "variable no definida"
$productos = [];
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
                <div class="header__search">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input placeholder="Buscar vinilos, CDs, casetes, artistas..." />
                </div>
                <div class="header__actions">
                    <a href="/carrito" class="header__icon-btn" aria-label="Carrito" style="position:relative">
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
                        <a class="btn btn--ghost" href="/perfil">Vender mi música</a>
                    </div>
                </div>
                <div class="hero__visual" aria-hidden="true">
                    <div style="position:relative;border-radius:0;overflow:hidden;border:1px solid var(--border);background:#151515;height:260px;display:grid;place-items:center;color:var(--muted)">Vista previa del marketplace</div>
                </div>
            </div>
        </section>

        <main class="main container">
            <!-- PRODUCTS -->
            <section id="productos" class="products">
                <div class="products__head">
                    <h2 class="products__title">Vinilos destacados</h2>
                    <div class="products__filter">
                        <button class="chip chip--active">Todos</button>
                        <button class="chip">NM</button>
                        <button class="chip">VG+</button>
                        <button class="chip">Raros</button>
                    </div>
                </div>

                <div class="products__grid">
                    <?php foreach ($productos as $producto): ?>
                    <article class="card">
                        <div class="card__media">
                            <img src="<?= htmlspecialchars($producto['imagen']) ?>" alt="<?= htmlspecialchars($producto['titulo']) ?>" />
                        </div>
                        <div class="card__body">
                            <div class="card__title"><?= htmlspecialchars($producto['titulo']) ?></div>
                            <div class="card__meta">
                                <span class="badge-state"><?= htmlspecialchars($producto['estado']) ?></span>
                                <span><?= htmlspecialchars($producto['anio']) ?> • <?= htmlspecialchars($producto['sello']) ?></span>
                            </div>
                            <div class="card__price"><?= htmlspecialchars($producto['precio']) ?> €</div>
                        </div>
                        <div class="card__footer">
                            <span style="color:var(--muted);font-size:.85rem"><?= htmlspecialchars($producto['envio']) ?></span>
                            <a href="/add-cart?id=<?= (int)$producto['id'] ?>" class="card__buy">Comprar</a>
                        </div>
                    </article>
                    <?php endforeach; ?>
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
</body>
</html>