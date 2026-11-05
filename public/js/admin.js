// Admin panel — gestión de solicitudes de tasación
// Maneja aprobaciones/rechazos via fetch() y actualización del DOM en tiempo real
//
// Endpoints consumidos:
//   POST /api/admin/onartu   → Aprobar producto (cambia onarpen_egoera a 'onartua')
//   POST /api/admin/baztertu → Rechazar producto (cambia onarpen_egoera a 'baztertua')
//   POST /api/admin/ezabatu  → Eliminar producto permanentemente

document.addEventListener('DOMContentLoaded', () => {
    // --- Estado y utilidades ---
    const pendingGrid = document.getElementById('pending-grid');
    const pendingCount = document.getElementById('pending-count');

    /**
     * Mostrar notificación toast en el panel admin
     * Reutiliza el patrón de toast brutalista del proyecto
     *
     * @param {string} message - Mensaje a mostrar
     * @param {string} type - 'info' | 'success' | 'error'
     */
    function showToast(message, type = 'info') {
        // Reutiliza el patrón de toast brutalista del proyecto
        let container = document.querySelector('.saskia-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'saskia-toast-container';
            document.body.appendChild(container);
        }
        const toast = document.createElement('div');
        toast.className = `saskia-toast saskia-toast--${type}`;
        toast.textContent = message;
        container.appendChild(toast);
        // Forzar reflow para animación
        requestAnimationFrame(() => toast.classList.add('saskia-toast--visible'));
        setTimeout(() => {
            toast.classList.remove('saskia-toast--visible');
            toast.addEventListener('transitionend', () => toast.remove());
        }, 3500);
    }

    /**
     * Animar eliminación de un elemento (fade out + slide)
     * Retorna una Promise que se resuelve cuando termina la animación
     *
     * @param {HTMLElement} element - Elemento a animar
     * @returns {Promise<void>}
     */
    function animateRemoval(element) {
        return new Promise(resolve => {
            element.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            element.style.opacity = '0';
            element.style.transform = 'translateX(20px)';
            element.addEventListener('transitionend', () => {
                element.remove();
                resolve();
            }, { once: true });
        });
    }

    /**
     * Actualizar contador visual de productos pendientes
     * Anima el cambio con un scale sutil
     *
     * @param {number} delta - Cambio a aplicar (+1 o -1)
     */
    function updatePendingCount(delta) {
        if (pendingCount) {
            const current = parseInt(pendingCount.textContent, 10) || 0;
            const next = Math.max(0, current + delta);
            pendingCount.textContent = next;
            // Animación sutil
            pendingCount.style.transform = 'scale(1.2)';
            setTimeout(() => pendingCount.style.transform = 'scale(1)', 150);
        }
    }

    // --- Manejador de botones Aprobar/Rechazar ---
    /**
     * Procesar una acción de aprobación o rechazo sobre un producto pendiente
     * Envía petición POST al endpoint correspondiente y actualiza el DOM
     *
     * @param {HTMLButtonElement} button - Botón clickeado
     * @param {string} action - 'onartu' (aprobar) | 'baztertu' (rechazar)
     */
    async function handleAction(button, action) {
        const card = button.closest('.pending-card');
        if (!card) return;

        const productId = card.dataset.productId;
        if (!productId) return;

        // Deshabilitar botones durante la petición (evita doble-click)
        const buttons = card.querySelectorAll('button');
        buttons.forEach(b => b.disabled = true);
        button.innerHTML = `<span class="spinner"></span> ${action === 'onartu' ? 'Aprobando...' : 'Rechazando...'}`;

        try {
            const endpoint = action === 'onartu' ? '/api/admin/onartu' : '/api/admin/baztertu';
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: parseInt(productId, 10) })
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                throw new Error(data.error || 'Error desconocido');
            }

            // Éxito: animar y eliminar la tarjeta
            showToast(data.message, 'success');
            await animateRemoval(card);
            updatePendingCount(-1);

            // Si no quedan tarjetas, mostrar estado vacío
            if (pendingGrid && pendingGrid.children.length === 0) {
                pendingGrid.innerHTML = `
                    <div class="admin__empty">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        <h3>No hay solicitudes pendientes</h3>
                        <p>Los artículos que los usuarios ofrezcan a SoinuTruk aparecerán aquí para su revisión.</p>
                    </div>
                `;
            }
        } catch (err) {
            // Error: reactivar botones y mostrar error
            buttons.forEach(b => b.disabled = false);
            button.innerHTML = action === 'onartu'
                ? `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Aprobar`
                : `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Rechazar`;
            showToast('Error: ' + err.message, 'error');
        }
    }

    // --- Delegación de eventos en el grid de pendientes ---
    // Los botones se generan dinámicamente por PHP, así que usamos delegación
    if (pendingGrid) {
        pendingGrid.addEventListener('click', (e) => {
            const approveBtn = e.target.closest('.admin-btn--approve');
            const rejectBtn = e.target.closest('.admin-btn--reject');

            if (approveBtn) {
                e.preventDefault();
                handleAction(approveBtn, 'onartu');
            } else if (rejectBtn) {
                e.preventDefault();
                handleAction(rejectBtn, 'baztertu');
            }
        });
    }

    // --- Eliminar producto aprobado (botones .admin-btn--delete) ---
    const approvedGrid = document.getElementById('approved-grid');
    const approvedCount = document.getElementById('active-products-count');

    /**
     * Eliminar un producto aprobado permanentemente
     * Pide confirmación al usuario antes de proceder
     *
     * @param {HTMLButtonElement} button - Botón de eliminar
     */
    async function handleDelete(button) {
        const card = button.closest('.approved-card');
        if (!card) return;

        const productId = card.dataset.productId;
        if (!productId) return;

        // Confirmación brutalista: el usuario debe confirmar la eliminación permanente
        const title = card.querySelector('.pending-card__title')?.textContent?.trim() || '';
        const confirmMsg = 'Ziur zaude artikulu hau betiko ezabatu nahi duzula?'
            + (title ? '\n\n"' + title + '"' : '');
        if (!confirm(confirmMsg)) return;

        button.disabled = true;
        button.innerHTML = `<span class="spinner"></span> Ezabatzen...`;

        try {
            const res = await fetch('/api/admin/ezabatu', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: parseInt(productId, 10) })
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                throw new Error(data.error || 'Error al eliminar');
            }

            showToast(data.message, 'success');
            await animateRemoval(card);

            // Actualizar el counter de productos activos
            if (approvedCount) {
                const current = parseInt(approvedCount.textContent, 10) || 0;
                approvedCount.textContent = Math.max(0, current - 1);
            }

            // Si no quedan productos, mostrar estado vacío
            if (approvedGrid && approvedGrid.children.length === 0) {
                approvedGrid.innerHTML = `
                    <div class="admin__empty">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <h3>No hay productos activos</h3>
                        <p>Los productos aprobados aparecerán aquí para su gestión.</p>
                    </div>
                `;
            }
        } catch (err) {
            button.disabled = false;
            button.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg> Ezabatu`;
            showToast('Error: ' + err.message, 'error');
        }
    }

    if (approvedGrid) {
        approvedGrid.addEventListener('click', (e) => {
            const deleteBtn = e.target.closest('.admin-btn--delete');
            if (deleteBtn) {
                e.preventDefault();
                handleDelete(deleteBtn);
            }
        });
    }

    // --- Spinner CSS inline para estados de carga ---
    // Inyecta estilos CSS dinámicamente para el spinner de carga
    const style = document.createElement('style');
    style.textContent = `
        .spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            margin-right: 0.4rem;
            vertical-align: middle;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .admin-btn--approve:disabled,
        .admin-btn--reject:disabled,
        .admin-btn--delete:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
    `;
    document.head.appendChild(style);
});
