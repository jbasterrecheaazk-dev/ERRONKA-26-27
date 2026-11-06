/**
 * public/js/saskia.js
 *
 * Módulo DWEC para gestionar la cesta (saskia) completa sin recargar página.
 *
 * Funcionalidades:
 * - Intercepta clics en botones ".btn-cart" → POST /api/saskia/gehitu
 * - Muestra notificación brutalista instantánea
 * - Actualiza el contador del header sin recarga
 * - En /carrito: hace fetch('/api/saskia') y renderiza dinámicamente las cards
 * - Delegación de eventos para eliminar item
 * - Checkout: POST /api/eskaerak/procesar
 *
 * Endpoints consumidos:
 *   GET  /api/saskia?action=index → JSON con items y count
 *   POST /api/saskia/gehitu      → JSON {success, error}
 *   POST /api/saskia/kendu       → JSON {success, error}
 *   POST /api/eskaerak/procesar  → JSON {success, error}
 */
(function () {
    'use strict';

    // URL base para todas las operaciones del carrito
    const API_CART = '/api/saskia';

    /** ------------------------------------------------------------------
     * Notificación brutalista tipo toast
     * Muestra un mensaje flotante que aparece/desaparece con opacidad.
     * Elimina toasts anteriores para no acumular elementos.
     *
     * @param {string} message - Texto a mostrar
     * @param {string} type - 'success' | 'error'
     */
    function showToast(message, type) {
        const existing = document.querySelector('.saskia-toast');
        if (existing) existing.remove();

        const el = document.createElement('div');
        el.className = 'saskia-toast saskia-toast--' + type;
        el.textContent = message;
        document.body.appendChild(el);

        requestAnimationFrame(function () {
            el.classList.add('saskia-toast--visible');
        });

        setTimeout(function () {
            el.classList.remove('saskia-toast--visible');
            setTimeout(function () { el.remove(); }, 300);
        }, 1800);
    }

    /** ------------------------------------------------------------------
     * Actualizar el badge del header con el contador real desde BD
     * Fetch al endpoint index y actualiza el span .saskia-badge
     */
    async function refreshCartBadge() {
        try {
            const res = await fetch(API_CART + '?action=index');
            if (!res.ok) return;
            const data = await res.json();
            const badge = document.querySelector('.saskia-badge');
            if (!badge) return;
            if (data.count > 0) {
                badge.textContent = data.count;
                badge.style.display = '';
            } else {
                badge.style.display = 'none';
            }
        } catch (e) {
            // Silencioso: no bloquea la navegación
        }
    }

    /** ------------------------------------------------------------------
     * Interceptar botones "Añadir al carrito" (.btn-cart)
     * Cada botón tiene data-product-id que se envía como produktu_id
     */
    function initAddToCartButtons() {
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-cart');
            if (!btn) return;

            e.preventDefault();
            const productId = btn.dataset.productId;
            if (!productId) return;

            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Añadiendo…';

            const form = new FormData();
            form.append('produktu_id', productId);

            fetch(API_CART + '/gehitu', {
                method: 'POST',
                body: form,
                credentials: 'same-origin'
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.error) throw new Error(data.error);
                    showToast('Producto añadido al carrito', 'success');
                    refreshCartBadge();
                })
                .catch(function () {
                    showToast('Error al añadir al carrito. Inténtalo de nuevo.', 'error');
                })
                .finally(function () {
                    btn.disabled = false;
                    btn.textContent = originalText;
                });
        });
    }

    /** ------------------------------------------------------------------
     * Renderizar la vista completa del carrito (/saskia)
     * Obtiene los items del carrito y genera HTML dinámico para cada uno.
     * También actualiza el resumen lateral y activa el botón de checkout.
     */
    async function renderCartView() {
        const skeleton = document.getElementById('saskiaSkeleton');
        const content  = document.getElementById('saskiaContent');
        const empty    = document.getElementById('saskiaEmpty');
        const itemsEl  = document.getElementById('saskiaItems');
        const summaryEl = document.getElementById('saskiaSummaryItems');
        const totalEl  = document.getElementById('saskiaTotal');
        const checkoutBtn = document.getElementById('saskiaCheckout');

        if (!skeleton || !content) return;

        skeleton.style.display = '';
        content.style.display  = 'none';
        if (empty) empty.style.display = 'none';

        try {
            const res = await fetch(API_CART + '?action=index');
            if (!res.ok) {
                if (res.status === 401) {
                    // Redirigir al login si no está autenticado
                    window.location.href = '/login';
                }
                throw new Error('Error al cargar la cesta');
            }
            const data = await res.json();
            const items = data.items || [];

            if (items.length === 0) {
                skeleton.style.display = 'none';
                if (empty) empty.style.display = '';
                return;
            }

            // Limpiar contenedores
            itemsEl.innerHTML = '';
            summaryEl.innerHTML = '';

            let subtotalNum = 0;

            items.forEach(function (item) {
                const saskiaId   = item.saskia_id;
                const productId  = item.produktu_id;
                const izenburua  = item.izenburua || '';
                const prezioa    = parseFloat(item.prezioa) || 0;
                const irudia     = item.irudia || null;
                subtotalNum += prezioa;

                // Crear card del item con imagen y datos
                const card = document.createElement('article');
                card.className = 'saskia-item';
                card.dataset.saskiaId = saskiaId;
                card.dataset.productId = productId;

                let imgHtml = '<div class="saskia-item__img saskia-item__img--empty">Sin imagen</div>';
                if (irudia) {
                    imgHtml = '<img class="saskia-item__img" src="/' + escapeHtml(irudia) + '" alt="' + escapeHtml(izenburua) + '">';
                }

                card.innerHTML =
                    '<div class="saskia-item__media">' + imgHtml + '</div>' +
                    '<div class="saskia-item__body">' +
                        '<h3 class="saskia-item__title">' + escapeHtml(izenburua) + '</h3>' +
                        '<div class="saskia-item__price">' + formatPrice(prezioa) + ' €</div>' +
                    '</div>' +
                    '<button class="saskia-item__remove" data-saskia-id="' + saskiaId + '" data-product-id="' + productId + '" aria-label="Eliminar">Eliminar</button>';

                itemsEl.appendChild(card);

                // Fila resumen
                const row = document.createElement('div');
                row.className = 'saskia-summary__row';
                row.innerHTML =
                    '<span>' + escapeHtml(izenburua) + '</span>' +
                    '<span>' + formatPrice(prezioa) + ' €</span>';
                summaryEl.appendChild(row);
            });

            skeleton.style.display = 'none';
            content.style.display  = '';

            totalEl.textContent = formatPrice(subtotalNum) + ' €';
            if (checkoutBtn) checkoutBtn.disabled = false;

            // Inicializar checkout
            if (checkoutBtn) {
                checkoutBtn.addEventListener('click', processCheckout);
            }
        } catch (err) {
            skeleton.innerHTML = '<div class="saskia__error">No se ha podido cargar la cesta. Inténtalo de nuevo.</div>';
        }
    }

    /** ------------------------------------------------------------------
     * Procesar checkout: enviar pedido al backend
     * POST /api/eskaerak/procesar crea el pedido y bloquea los productos
     */
    async function processCheckout() {
        const btn = document.getElementById('saskiaCheckout');
        btn.disabled = true;
        btn.textContent = 'Procesando...';
        try {
            const res = await fetch('/api/eskaerak/procesar', { method: 'POST' });
            const data = await res.json();

            if (res.status === 409) {
                // Producto vendido: recargar carrito, mostrar error y refrescar badge
                showToast(data.error, 'error');
                btn.disabled = false;
                btn.textContent = 'Tramitar pago';
                renderCartView();
                refreshCartBadge();
                return;
            }

            if (!res.ok) throw new Error(data.error || 'Error desconocido');

            // Éxito: redirección brutalista (pantalla de confirmación)
            document.body.innerHTML =
                '<div style="min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem; text-align: center; background: #121212;">' +
                '<div style="max-width: 480px; width: 100%;">' +
                '<div style="width: 80px; height: 80px; margin: 0 auto 2rem; border: 3px solid #F5F5F5; display: flex; align-items: center; justify-content: center;">' +
                '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#F5F5F5" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>' +
                '</div>' +
                '<h1 style="font-size: 3rem; margin-bottom: 1rem; font-weight: 700;">Pedido confirmado</h1>' +
                '<p style="font-size: 1.25rem; color: #a0a0a0; margin-bottom: 3rem; line-height: 1.6;">Gracias por tu compra en SoinuTruk.</p>' +
                '<a href="/perfil" class="btn btn--primary" style="display: inline-block; padding: 1rem 2.5rem; font-size: 1.1rem; margin-bottom: 1rem;">Ver mis compras</a>' +
                '<p style="font-size: 0.9rem; color: #666; margin-top: 2rem;">Recibirás un correo con los detalles de tu pedido</p>' +
                '</div>' +
                '</div>';
        } catch (err) {
            showToast('Error en el pago: ' + err.message, 'error');
            btn.disabled = false;
            btn.textContent = 'Tramitar pago';
        }
    }

    /** ------------------------------------------------------------------
     * Delegación de eventos: eliminar en el carrito
     * Los botones .saskia-item__remove se generan dinámicamente,
     * por lo que usamos delegación en document.
     */
    function initCartInteractions() {
        document.addEventListener('click', function (e) {
            // Botón eliminar
            const removeBtn = e.target.closest('.saskia-item__remove');
            if (removeBtn) {
                e.preventDefault();
                const saskiaId = removeBtn.dataset.saskiaId;
                const productId = removeBtn.dataset.productId;
                deleteItem(saskiaId, productId, removeBtn.closest('.saskia-item'));
                return;
            }
        });
    }

    /** ------------------------------------------------------------------
     * Eliminar un item de la cesta
     * POST /api/saskia/kendu con saskia_id y produktu_id
     *
     * @param {number} saskiaId - ID del registro en la tabla saskia
     * @param {number} productId - ID del producto
     * @param {HTMLElement} itemEl - Elemento DOM a eliminar
     */
    function deleteItem(saskiaId, productId, itemEl) {
        const form = new FormData();
        form.append('saskia_id', saskiaId);
        form.append('produktu_id', productId);

        fetch(API_CART + '/kendu', {
            method: 'POST',
            body: form,
            credentials: 'same-origin'
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.error) throw new Error(data.error);
                showToast('Producto eliminado del carrito', 'success');
                if (itemEl) itemEl.remove();
                refreshCartBadge();
                // Recalcular totales si hay más items
                recalcSummary();
            })
            .catch(function () {
                showToast('Error al eliminar.', 'error');
            });
    }

    /** ------------------------------------------------------------------
     * Recalcular totales tras eliminar un item
     * Refresca el summary lateral y el total sin recargar toda la página
     */
    async function recalcSummary() {
        try {
            const res = await fetch(API_CART + '?action=index');
            if (!res.ok) return;
            const data = await res.json();
            const totalEl = document.getElementById('saskiaTotal');
            const summaryEl = document.getElementById('saskiaSummaryItems');
            const checkoutBtn = document.getElementById('saskiaCheckout');

            if (!totalEl) return;

            let subtotal = 0;
            if (summaryEl) summaryEl.innerHTML = '';

            (data.items || []).forEach(function (item) {
                const prezioa = parseFloat(item.prezioa) || 0;
                subtotal += prezioa;

                if (summaryEl) {
                    const row = document.createElement('div');
                    row.className = 'saskia-summary__row';
                    row.innerHTML =
                        '<span>' + escapeHtml(item.izenburua || '') + '</span>' +
                        '<span>' + formatPrice(prezioa) + ' €</span>';
                    summaryEl.appendChild(row);
                }
            });

            totalEl.textContent = formatPrice(subtotal) + ' €';
            if (checkoutBtn) checkoutBtn.disabled = subtotal === 0;
        } catch (e) {
            // Silencioso
        }
    }

    /** ------------------------------------------------------------------
     * Helpers
     * ------------------------------------------------------------------ */
    // Escapar HTML para prevenir XSS al insertar contenido dinámico
    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // Formatear número como precio español (coma como separador decimal)
    function formatPrice(num) {
        return Number(num).toFixed(2).replace('.', ',');
    }

    /** ------------------------------------------------------------------
     * Inicialización principal
     * Configura todos los listeners y, si estamos en /carrito,
     * renderiza la vista del carrito.
     * ------------------------------------------------------------------ */
    function init() {
        initAddToCartButtons();
        initCartInteractions();

        // Si estamos en la página del carrito, renderizar su contenido
        if (window.location.pathname === '/carrito' || window.location.pathname === '/carrito/' || window.location.pathname === '/saskia' || window.location.pathname === '/saskia/') {
            renderCartView();
        }
    }

    // Exponer API global para interacción desde otros scripts
    window.SoinutrukSaskia = {
        addToCart: function (productId) {
            var btn = document.querySelector('.btn-cart[data-product-id="' + productId + '"]');
            if (btn) btn.click();
        },
        refreshBadge: refreshCartBadge
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
