<?php
// admin.php — Panel de Administración
// $estadisticas, $pendientes, $azkenEskaerak, $contadorPendientes vienen del AdminController
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoinuTruk — Administración</title>
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
                    <a href="/perfil" class="header__icon-btn" aria-label="Perfil">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </a>
                    <a href="/logout" class="header__btn header__btn--logout" aria-label="Cerrar sesión">Cerrar sesión</a>
                </div>
            </div>
        </header>

        <main class="admin">
            <div class="container">
                <header class="admin__header">
                    <h1 class="admin__title">Panel de Administración</h1>
                    <p class="admin__subtitle">Gestión de tasaciones y métricas del marketplace</p>
                </header>

                <!-- KPI Cards -->
                <section class="admin__stats" aria-label="Estadísticas principales">
                    <article class="stat-card">
                        <div class="stat-card__icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                            </svg>
                        </div>
                        <div class="stat-card__content">
                            <div class="stat-card__value"><?= number_format($estadisticas['total_ingresos'], 2) ?> €</div>
                            <div class="stat-card__label">Total Ventas Acumuladas</div>
                        </div>
                    </article>

                    <article class="stat-card">
                        <div class="stat-card__icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                            </svg>
                        </div>
                        <div class="stat-card__content">
                            <div class="stat-card__value"><?= (int)$estadisticas['total_pedidos'] ?></div>
                            <div class="stat-card__label">Pedidos Completados</div>
                        </div>
                    </article>

                    <article class="stat-card">
                        <div class="stat-card__icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        </div>
                        <div class="stat-card__content">
                            <div class="stat-card__value"><?= (int)$estadisticas['total_usuarios'] ?></div>
                            <div class="stat-card__label">Usuarios Registrados</div>
                        </div>
                    </article>

                    <article class="stat-card">
                        <div class="stat-card__icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
                        <div class="stat-card__content">
                            <div class="stat-card__value"><?= (int)$estadisticas['productos_activos'] ?></div>
                            <div class="stat-card__label">Productos Activos</div>
                        </div>
                    </article>
                </section>

                <!-- Solicitudes Pendientes -->
                <section class="admin__section" aria-label="Solicitudes pendientes de tasación">
                    <div class="admin__section-header">
                        <h2 class="admin__section-title">Solicitudes Pendientes de Tasación</h2>
                        <span class="admin__badge" id="pending-count"><?= (int)$contadorPendientes ?></span>
                    </div>

                    <?php if (empty($pendientes)): ?>
                        <div class="admin__empty">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            <h3>No hay solicitudes pendientes</h3>
                            <p>Los artículos que los usuarios ofrezcan a SoinuTruk aparecerán aquí para su revisión.</p>
                        </div>
                    <?php else: ?>
                        <div class="admin__pending-grid" id="pending-grid">
                            <?php foreach ($pendientes as $prod): ?>
                                <article class="pending-card" data-product-id="<?= (int)$prod['id'] ?>">
                                    <div class="pending-card__media">
                                        <?php if (!empty($prod['irudia'])): ?>
                                            <img src="/<?= htmlspecialchars($prod['irudia']) ?>" alt="<?= htmlspecialchars($prod['izenburua']) ?>" loading="lazy">
                                        <?php else: ?>
                                            <div class="pending-card__media--empty">Sin imagen</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="pending-card__body">
                                        <h3 class="pending-card__title"><?= htmlspecialchars($prod['izenburua']) ?></h3>
                                        <div class="pending-card__meta">
                                            <span class="pending-card__category"><?= htmlspecialchars($prod['categoria_nombre'] ?? 'Sin categoría') ?></span>
                                            <span class="pending-card__price"><?= number_format($prod['prezioa'], 2) ?> €</span>
                                        </div>
                                        <div class="pending-card__seller">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                                            </svg>
                                            <span><?= htmlspecialchars($prod['solicitante_nombre']) ?></span>
                                            <span class="pending-card__email"><?= htmlspecialchars($prod['solicitante_email']) ?></span>
                                        </div>
                                        <div class="pending-card__date">
                                            <?= htmlspecialchars(date('d/m/Y H:i', strtotime($prod['sortze_data']))) ?>
                                        </div>
                                    </div>
                                    <div class="pending-card__actions">
                                        <button class="btn btn--primary admin-btn--approve" data-id="<?= (int)$prod['id'] ?>">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                            Aprobar
                                        </button>
                                        <button class="btn btn--danger admin-btn--reject" data-id="<?= (int)$prod['id'] ?>">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                            Rechazar
                                        </button>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Actividad Reciente -->
                <section class="admin__section" aria-label="Actividad reciente de ventas">
                    <h2 class="admin__section-title">Actividad Reciente de Ventas</h2>

                    <?php if (empty($azkenEskaerak)): ?>
                        <div class="admin__empty">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            <h3>Sin actividad reciente</h3>
                            <p>Las ventas completadas aparecerán aquí.</p>
                        </div>
                    <?php else: ?>
                        <div class="admin__table-wrapper">
                            <table class="admin__table">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Producto</th>
                                        <th>Comprador</th>
                                        <th>Importe</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($azkenEskaerak as $pedido): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($pedido['data']))) ?></td>
                                            <td><?= htmlspecialchars($pedido['producto_izenburua']) ?></td>
                                            <td>
                                                <div><?= htmlspecialchars($pedido['erosle_nombre']) ?></div>
                                                <small><?= htmlspecialchars($pedido['erosle_email']) ?></small>
                                            </td>
                                            <td><?= number_format($pedido['totala'], 2) ?> €</td>
                                            <td><span class="admin__status admin__status--completed">Completado</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>

        <footer class="footer">
            <div class="container footer__inner">
                <div>© 2026 SoinuTruk — Panel de Administración</div>
            </div>
        </footer>
    </div>

    <script src="/public/js/admin.js"></script>
</body>
</html>