/**
 * SoinuTruk - Módulo de Favoritos
 *
 * Gestiona los favoritos del usuario usando localStorage.
 * Los favoritos son locales al navegador (no se sincronizan con el servidor).
 *
 * Funcionalidades:
 * - toggleFavorite(id): añade/quita un producto de favoritos
 * - isFavorite(id): comprueba si un producto está en favoritos
 * - getFavorites(): devuelve array de IDs de favoritos
 * - updateFavoriteButtons(): actualiza visualmente todos los botones de corazón
 */
(function() {
    'use strict';

    const STORAGE_KEY = 'soinutruk_favoritos';

    /**
     * Obtener lista de favoritos desde localStorage
     * @returns {number[]} Array de IDs de productos favoritos
     */
    function getFavorites() {
        try {
            const data = localStorage.getItem(STORAGE_KEY);
            return data ? JSON.parse(data) : [];
        } catch (e) {
            console.error('Error reading favorites from localStorage:', e);
            return [];
        }
    }

    /**
     * Guardar lista de favoritos en localStorage
     * @param {number[]} favorites - Array de IDs
     */
    function setFavorites(favorites) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(favorites));
        } catch (e) {
            console.error('Error saving favorites to localStorage:', e);
        }
    }

    /**
     * Verificar si un producto está en favoritos
     * @param {number} id - ID del producto
     * @returns {boolean}
     */
    function isFavorite(id) {
        return getFavorites().includes(id);
    }

    /**
     * Añadir o quitar un producto de favoritos
     * @param {number} id - ID del producto
     * @returns {boolean} true si ahora está favorito, false si se quitó
     */
    function toggleFavorite(id) {
        let favorites = getFavorites();
        const index = favorites.indexOf(id);

        if (index >= 0) {
            // Quitar de favoritos
            favorites.splice(index, 1);
        } else {
            // Añadir a favoritos
            favorites.push(id);
        }

        setFavorites(favorites);
        return favorites.includes(id);
    }

    /**
     * Actualizar visualmente todos los botones de favoritos en el DOM
     * Busca elementos con clase .btn-favorite y data-product-id
     */
    function updateFavoriteButtons() {
        const buttons = document.querySelectorAll('.btn-favorite');

        buttons.forEach(btn => {
            const productId = parseInt(btn.dataset.productId, 10);
            if (isNaN(productId)) return;

            const favorite = isFavorite(productId);
            btn.classList.toggle('btn-favorite--active', favorite);
            btn.setAttribute('aria-pressed', favorite);

            // Actualizar icono (corazón lleno/vacío y color)
            const icon = btn.querySelector('svg');
            if (icon) {
                if (favorite) {
                    // Corazón rojo relleno
                    icon.setAttribute('fill', 'currentColor');
                    btn.classList.add('btn-favorite--active');
                } else {
                    // Corazón negro vacío
                    icon.setAttribute('fill', 'none');
                    btn.classList.remove('btn-favorite--active');
                }
            }
        });
    }

    /**
     * Inicializar eventos de favoritos
     * Escucha clics en botones .btn-favorite y delega a toggleFavorite
     */
    function init() {
        // Delegación de eventos para botones de favoritos
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-favorite');
            if (!btn) return;

            e.preventDefault();
            e.stopPropagation();

            const productId = btn.dataset.productId;
            if (!productId) return;

            const id = parseInt(productId, 10);
            if (isNaN(id)) return;

            // Toggle favorito
            const nowFavorite = toggleFavorite(id);

            // Actualizar UI
            const icon = btn.querySelector('svg');
            if (icon) {
                if (nowFavorite) {
                    icon.setAttribute('fill', 'currentColor');
                    btn.classList.add('btn-favorite--active');
                } else {
                    icon.setAttribute('fill', 'none');
                    btn.classList.remove('btn-favorite--active');
                }
            }
            btn.setAttribute('aria-pressed', nowFavorite);

            // Notificar al usuario
            showFavoriteNotification(id, nowFavorite);
        });

        // Actualizar botones al cargar página
        updateFavoriteButtons();
    }

    /**
     * Mostrar notificación temporal al cambiar favorito
     * @param {number} id - ID del producto
     * @param {boolean} isFavorite - Si ahora está en favoritos
     */
    function showFavoriteNotification(id, isFavorite) {
        // Crear notificación flotante
        const notification = document.createElement('div');
        notification.className = 'favorite-notification';
        notification.textContent = isFavorite
            ? `★ Añadido a favoritos`
            : '☆ Eliminado de favoritos';

        notification.className = `favorite-notification ${isFavorite ? 'favorite-notification--success' : 'favorite-notification--removed'}`;

        document.body.appendChild(notification);

        // Animar aparición
        requestAnimationFrame(() => {
            notification.style.opacity = '1';
        });
        // The CSS class handles the styling

        // Eliminar después de 2 segundos
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 2000);
    }

    // Exportar funciones globales para uso externo
    window.SoinutrukFavorites = {
        getFavorites,
        isFavorite,
        toggleFavorite,
        updateFavoriteButtons,
        STORAGE_KEY
    };

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
