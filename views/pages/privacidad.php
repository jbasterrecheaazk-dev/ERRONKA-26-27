<?php
/**
 * VIEWS/PAGES/PRIVACIDAD.PHP
 *
 * Página de Política de Privacidad de SoinuTruk.
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
    <title>SoinuTruk — Política de Privacidad</title>
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
            <h1 class="legal__title">Política de Privacidad</h1>
            <p class="legal__updated">Última actualización: Enero 2026</p>

            <section class="legal__section">
                <h2>1. Responsable del tratamiento</h2>
                <p><strong>SoinuTruk</strong> es la plataforma que facilita la compraventa de música física entre particulares. El responsable del tratamiento de sus datos personales es la entidad que opera esta plataforma, con domicilio a efectos de contacto en los términos establecidos por la legislación vigente.</p>
            </section>

            <section class="legal__section">
                <h2>2. Datos que recopilamos</h2>
                <p>Recopilamos y tratamos los siguientes tipos de datos personales:</p>
                <ul>
                    <li><strong>Datos de registro:</strong> nombre, apellidos, dirección de correo electrónico y contraseña (encriptada).</li>
                    <li><strong>Datos de perfil:</strong> información que usted decida proporcionar voluntariamente en su perfil de usuario.</li>
                    <li><strong>Datos de transacción:</strong> información relacionada con las compras y ventas realizadas a través de la plataforma (productos ofrecidos, precios, historial de pedidos).</li>
                    <li><strong>Datos técnicos:</strong> dirección IP, tipo de navegador, sistema operativo, datos de navegación y cookies.</li>
                </ul>
            </section>

            <section class="legal__section">
                <h2>3. Finalidad del tratamiento</h2>
                <p>Utilizamos sus datos personales para las siguientes finalidades:</p>
                <ul>
                    <li>Gestionar su cuenta de usuario y facilitar el acceso a la plataforma.</li>
                    <li>Permitir la publicación, búsqueda y compra de productos en el marketplace.</li>
                    <li>Procesar transacciones y gestionar pedidos, pagos y envíos.</li>
                    <li>Enviar comunicaciones relacionadas con su cuenta, pedidos y novedades de la plataforma.</li>
                    <li>Mejorar nuestros servicios, analizar el uso de la plataforma y desarrollar nuevas funcionalidades.</li>
                    <li>Cumplir con obligaciones legales y prevenir fraudes.</li>
                </ul>
            </section>

            <section class="legal__section">
                <h2>4. Base legal del tratamiento</h2>
                <p>El tratamiento de sus datos personales se basa en:</p>
                <ul>
                    <li><strong>Ejecución contractual:</strong> el tratamiento es necesario para la prestación del servicio solicitado.</li>
                    <li><strong>Consentimiento:</strong> cuando usted ha otorgado su consentimiento expreso para tratamientos específicos.</li>
                    <li><strong>Interés legítimo:</strong> para mejorar nuestros servicios y prevenir fraudes.</li>
                    <li><strong>Obligación legal:</strong> cuando la normativa aplica requerimientos de conservación de datos.</li>
                </ul>
            </section>

            <section class="legal__section">
                <h2>5. Conservación de datos</h2>
                <p>Sus datos personales se conservarán durante el tiempo necesario para cumplir con la finalidad para la que fueron recogidos y para determinar las posibles responsabilidades derivadas. En concreto:</p>
                <ul>
                    <li>Los datos de cuenta se conservarán mientras dure su relación como usuario.</li>
                    <li>Los datos de transacción se conservarán durante los plazos legalmente exigidos.</li>
                    <li>Los datos de navegación se conservarán por un período máximo de 12 meses.</li>
                </ul>
            </section>

            <section class="legal__section">
                <h2>6. Destinatarios y cesiones</h2>
                <p>Sus datos no serán cedidos a terceros salvo que exista obligación legal o sea necesario para la prestación del servicio (por ejemplo, servicios de envío de paquetes). No se realizan decisiones automatizadas ni profiling.</p>
            </section>

            <section class="legal__section">
                <h2>7. Derechos del interesado</h2>
                <p>D Conformidad con la normativa de protección de datos vigente, usted tiene derecho a:</p>
                <ul>
                    <li><strong>Acceso:</strong> conocer qué datos personales tratamos y cómo los usamos.</li>
                    <li><strong>Rectificación:</strong> solicitar la corrección de datos inexactos o incompletos.</li>
                    <li><strong>Supresión:</strong> solicitar la eliminación de sus datos cuando ya no sean necesarios.</li>
                    <li><strong>Limitación:</strong> solicitar la limitación del tratamiento en determinadas circunstancias.</li>
                    <li><strong>Portabilidad:</strong> recibir sus datos en un formato estructurado y de uso común.</li>
                    <li><strong>Oposición:</strong> oponerse al tratamiento de sus datos por motivos relacionados con su situación personal.</li>
                </ul>
                <p>Para ejercer estos derechos, puede contactarnos a través de los medios indicados en esta política.</p>
            </section>

            <section class="legal__section">
                <h2>8. Cookies</h2>
                <p>Utilizamos cookies propias y de terceros para mejorar su experiencia de navegación:</p>
                <ul>
                    <li><strong>Cookies técnicas:</strong> necesarias para el funcionamiento de la plataforma (sesión, autenticación, carrito).</li>
                    <li><strong>Cookies analíticas:</strong> nos permiten medir y analizar el uso de la plataforma para mejorar nuestros servicios.</li>
                    <li><strong>Cookies de favoritos:</strong> almacenan localmente sus productos marcados como favoritos.</li>
                </ul>
                <p>Puede configurar su navegador para rechazar las cookies, aunque esto podría afectar certaines funcionalidades de la plataforma.</p>
            </section>

            <section class="legal__section">
                <h2>9. Seguridad de los datos</h2>
                <p>Implementamos medidas de seguridad técnicas y organizativas apropiadas para proteger sus datos personales contra el tratamiento no autorizado o ilícito, así como contra la pérdida, destrucción o daño accidentales. En particular:</p>
                <ul>
                    <li>Las contraseñas se almacenan encriptadas mediante bcrypt.</li>
                    <li>Las comunicaciones se realizan a través de protocolos seguros (HTTPS).</li>
                    <li>Accedemos a los datos solo para las finalidades descritas en esta política.</li>
                </ul>
            </section>

            <section class="legal__section">
                <h2>10. Menores de edad</h2>
                <p>SoinuTruk no está dirigida a menores de 16 años. No recopilamos conscientemente datos de menores. Si tiene menos de 16 años, no debe registrarse en la plataforma sin el consentimiento de sus padres o tutores legales.</p>
            </section>

            <section class="legal__section">
                <h2>11. Cambios en la política de privacidad</h2>
                <p>Nos reservamos el derecho a modificar esta política en cualquier momento. Cualquier cambio será publicado en esta página con la fecha de última actualización. Le recomendamos revisarla periódicamente.</p>
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
