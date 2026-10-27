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
                <div class="header__brand">
                    <img class="header__logo-icon" src="/public/img/LogoErronka_Logo_sinfonfo.png" alt="SoinuTruk logo">
                    <div class="header__logo">Soinu<span>Truk</span></div>
                </div>
                <div class="header__search">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input placeholder="Buscar vinilos, CDs, casetes, artistas..." />
                </div>
                <div class="header__actions">
                    <button class="header__icon-btn" aria-label="Carrito" style="position:relative">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 12.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        <span class="badge">3</span>
                    </button>
                    <button class="header__icon-btn" aria-label="Login">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </button>
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
                        <a class="btn btn--ghost" href="#">Vender mi música</a>
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
                    <!-- Card 1 -->
                    <article class="card">
                        <div class="card__media">
                            <img src="https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?q=80&w=1200&auto=format&fit=crop" alt="Vinilo Pink Floyd The Dark Side of the Moon" />
                        </div>
                        <div class="card__body">
                            <div class="card__title">Pink Floyd — The Dark Side of the Moon: Edición remasterizada 30 aniversario con libreto extra y funda de tela</div>
                            <div class="card__meta">
                                <span class="badge-state">NM</span>
                                <span>1973 • Harvest</span>
                            </div>
                            <div class="card__price">89,00 €</div>
                        </div>
                        <div class="card__footer">
                            <span style="color:var(--muted);font-size:.85rem">Envío gratis</span>
                            <button class="card__buy">Comprar</button>
                        </div>
                    </article>

                    <!-- Card 2 -->
                    <article class="card">
                        <div class="card__media">
                            <img src="https://images.unsplash.com/photo-1483412033650-1015ddeb83d1?q=80&w=1200&auto=format&fit=crop" alt="Vinilo Fleetwood Mac Rumours" />
                        </div>
                        <div class="card__body">
                            <div class="card__title">Rumours 1977</div>
                            <div class="card__meta">
                                <span class="badge-state">VG+</span>
                                <span>1977 • Warner</span>
                            </div>
                            <div class="card__price">64,00 €</div>
                        </div>
                        <div class="card__footer">
                            <span style="color:var(--muted);font-size:.85rem">Recogida local</span>
                            <button class="card__buy">Comprar</button>
                        </div>
                    </article>

                    <!-- Card 3 -->
                    <article class="card">
                        <div class="card__media">
                            <img src="https://images.unsplash.com/photo-1514320290765-9e1a1e1d3c2c?q=80&w=1200&auto=format&fit=crop" alt="Vinilo Nirvana Nevermind" />
                        </div>
                        <div class="card__body">
                            <div class="card__title">Nirvana — Nevermind</div>
                            <div class="card__meta">
                                <span class="badge-state">NM</span>
                                <span>1991 • Geffen</span>
                            </div>
                            <div class="card__price">72,00 €</div>
                        </div>
                        <div class="card__footer">
                            <span style="color:var(--muted);font-size:.85rem">Envío 24h</span>
                            <button class="card__buy">Comprar</button>
                        </div>
                    </article>

                    <!-- Card 4 -->
                    <article class="card">
                        <div class="card__media">
                            <img src="https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?q=80&w=1200&auto=format&fit=crop" alt="Vinilo edición limitada The Beatles" />
                        </div>
                        <div class="card__body">
                            <div class="card__title">The Beatles — Abbey Road (Ed. 50º)</div>
                            <div class="card__meta">
                                <span class="badge-state">M</span>
                                <span>2019 • Apple</span>
                            </div>
                            <div class="card__price">120,00 €</div>
                        </div>
                        <div class="card__footer">
                            <span style="color:var(--muted);font-size:.85rem">Edición limitada</span>
                            <button class="card__buy">Comprar</button>
                        </div>
                    </article>
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