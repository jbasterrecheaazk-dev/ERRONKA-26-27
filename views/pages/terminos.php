<?php
/**
 * VIEWS/PAGES/TERMINOS.PHP
 *
 * Página de Términos y Condiciones del servicio SoinuTruk.
 * Marketplace C2C de compraventa de música física (vinilos, CDs, casetes).
 */
// Contador del carrito (SSR) para el header
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
    <title>SoinuTruk — Términos y Condiciones</title>
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
                        <?php if (isset($_SESSION['usuario_id']) && $num_productos_carrito > 0): ?>
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

        <!-- MAIN: Contenido legal -->
        <main class="container" style="padding: 60px 20px; max-width: 900px;">
            <h1 class="legal__title">Términos y Condiciones de Uso</h1>
            <p class="legal__updated">Última actualización: Enero 2026</p>

            <section class="legal__section">
                <h2>1. Aceptación de los términos</h2>
                <p>Al acceder y utilizar la plataforma SoinuTruk, usted acepta estos términos y condiciones en su totalidad. Si no está de acuerdo con alguna parte de los términos, no podrá acceder a la plataforma.</p>
            </section>

            <section class="legal__section">
                <h2>2. Naturaleza del servicio</h2>
                <p>SoinuTruk es un marketplace C2C (consumidor a consumidor) que conecta a particulares para la compraventa de música física: vinilos, CDs, casetes y otros formatos analógicos y digitales. SoinuTruk actúa únicamente como intermediario tecnológico y no como parte directa en las transacciones entre usuarios.</p>
            </section>

            <section class="legal__section">
                <h2>3. Registro y cuenta de usuario</h2>
                <p>Para utilizar ciertas funcionalidades de la plataforma es necesario crear una cuenta. Usted se compromete a:</p>
                <ul>
                    <li>Proporcionar información veraz, exacta y actualizada.</li>
                    <li>Mantener la confidencialidad de sus credenciales de acceso.</li>
                    <li>Responsabilizarse de todas las actividades realizadas bajo su cuenta.</li>
                </ul>
            </section>

            <section class="legal__section">
                <h2>4. Publicación de productos</h2>
                <p>Como vendedor, usted se compromete a:</p>
                <ul>
                    <li>Publicar descripciones exactas y fotografías reales de los productos ofrecidos.</li>
                    <li>No publicar productos falsificados, pirateados o que infrinjan derechos de propiedad intelectual.</li>
                    <li>Garantizar que es el legítimo propietario o tiene autoridad para vender el producto.</li>
                </ul>
                <p>Todos los productos publicados pasan por un proceso de revisión y aprobación por parte de nuestro equipo de moderación antes de ser publicados en el catálogo.</p>
            </section>

            <section class="legal__section">
                <h2>5. Proceso de compra y venta</h2>
                <p>Las transacciones entre compradores y vendedores se realizan bajo las siguientes reglas:</p>
                <ul>
                    <li>El precio publicado es el precio final de venta.</li>
                    <li>Los gastos de envío corren por cuenta del comprador, salvo indicación contraria.</li>
                    <li>El vendedor debe entregar el producto en un plazo no superior a 7 días naturales.</li>
                    <li>El comprador debe inspeccionar el producto al recibirlo y reportar cualquier discrepancia en un plazo de 48 horas.</li>
                </ul>
            </section>

            <section class="legal__section">
                <h2>6. Devoluciones y reclamaciones</h2>
                <p>Dado que se trata de un mercado de segunda mano, las ventas realizadas a través de SoinuTruk no son reversibles salvo en los siguientes casos:</p>
                <ul>
                    <li>El producto recibido es significativamente diferente a la descripción publicada.</li>
                    <li>El producto es falsificado o counterfeit.</li>
                    <li>El producto no ha sido entregado en el plazo estipulado.</li>
                </ul>
                <p>Para solicitar una reclamación, el comprador debe contactar con el soporte a través de la plataforma en un plazo máximo de 7 días naturales desde la recepción del producto.</p>
            </section>

            <section class="legal__section">
                <h2>7. Propiedad intelectual</h2>
                <p>Todo el contenido de SoinuTruk (logos, diseños, textos, código fuente, base de datos) está protegido por derechos de propiedad intelectual. Queda prohibida la reproducción, distribución o transformación sin autorización expresa.</p>
                <p>Los usuarios conservan la propiedad de los productos que publican, pero otorgan a SoinuTruk una licencia limitada para mostrar y promocionar dichos productos dentro de la plataforma.</p>
            </section>

            <section class="legal__section">
                <h2>8. Limitación de responsabilidad</h2>
                <p>SoinuTruk no se responsabiliza de:</p>
                <ul>
                    <li>La calidad, estado o autenticidad de los productos publicados por los usuarios.</li>
                    <li>Disputas entre compradores y vendedores una vez cerrada la transacción.</li>
                    <li>Daños indirectos, incidentales o consecuentes derivados del uso de la plataforma.</li>
                </ul>
                <p>La responsabilidad máxima de SoinuTruk se limita al importe pagado por el usuario en los últimos 12 meses.</p>
            </section>

            <section class="legal__section">
                <h2>9. Modificaciones de los términos</h2>
                <p>SoinuTruk se reserva el derecho de modificar estos términos en cualquier momento. Las modificaciones entrarán en vigor desde su publicación. El uso continuado de la plataforma tras los cambios implica la aceptación de los nuevos términos.</p>
            </section>

            <section class="legal__section">
                <h2>10. Legislación aplicable</h2>
                <p>Estos términos se rigen por la legislación española. Para cualquier controversia, las partes se someten a los juzgados y tribunales del domicilio del usuario consumidor.</p>
            </section>
        </main>

        <!-- FOOTER -->
        <footer class="footer">
            <div class="container footer__inner">
                <div>© 2026 SoinuTruk — Marketplace C2C de música física</div>
                <div><a href="/terminos">Términos</a> • <a href="/privacidad">Privacidad</a></div>
            </div>
        </footer>
    </div>
</body>
</html>
