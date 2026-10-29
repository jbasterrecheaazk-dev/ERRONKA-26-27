/*
 * SoinuTruk - Módulo de Búsqueda y Filtrado con Paginación
 *
 * Implementa búsqueda en tiempo real, filtrado por categoría y paginación.
 * Usa fetch API con debounce para evitar peticiones excesivas.
 */
(function() {
    'use strict';

    // Configuración
    const DEBOUNCE_DELAY = 300; // ms
    const API_ENDPOINT = '/api/productos';

    // Estado global
    let searchTimeout = null;
    let currentPage = 1;
    let currentLimit = 20;
    let totalPages = 1;

    /**
     * Realizar petición de búsqueda/filtrado con paginación
     * @param {string} q - Término de búsqueda
     * @param {number} kategoriaId - ID de categoría (0 = todas)
     * @param {number} page - Página actual
     * @param {number} limit - Límite por página
     * @returns {Promise<Object>} Objeto con productos y datos de paginación
     */
    async function fetchProductos(q, kategoriaId, page, limit) {
        const params = new URLSearchParams();
        if (q) params.set('q', q);
        if (kategoriaId && kategoriaId !== '0') params.set('kategoria', kategoriaId);
        params.set('pagina', page);
        params.set('limite', limit);

        const url = `${API_ENDPOINT}?${params.toString()}`;

        try {
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return await response.json();
        } catch (error) {
            console.error('Error fetching productos:', error);
            return { productos: [], paginacion: { total: 0 } };
        }
    }

    /**
     * Renderizar productos en el grid
     * @param {Array} productos - Array de productos a renderizar
     * @param {Object} paginacion - Datos de paginación
     * @param {HTMLElement} container - Contenedor del grid
     */
    function renderProductos(productos, paginacion, container) {
        if (!container) return;

        // Limpiar grid
        container.innerHTML = '';

        if (!productos || productos.length === 0) {
            container.innerHTML = `
                <div class="products__empty">
                    No se encontraron productos con esos criterios de búsqueda.
                </div>
            `;
            return;
        }

        // Renderizar cada producto
        productos.forEach(producto => {
            const card = createProductCard(producto);
            container.appendChild(card);
        });

        // Reattach event listeners para navegación
        attachCardNavigation(container);
    }

    /**
     * Crear tarjeta de producto (HTML string)
     * Mantiene la estructura BEM original
     * @param {Object} producto - Datos del producto
     * @returns {HTMLElement} Tarjeta del producto
     */
    function createProductCard(producto) {
        const article = document.createElement('article');
        article.className = 'card';
        article.dataset.url = `/producto?id=${producto.id}`;
        article.dataset.productId = producto.id;

        // Imagen
        const mediaDiv = document.createElement('div');
        mediaDiv.className = 'card__media';

        if (producto.irudia) {
            const img = document.createElement('img');
            img.src = `/${producto.irudia}`;
            img.alt = producto.izenburua;
            img.loading = 'lazy';
            mediaDiv.appendChild(img);
        } else {
            const noImage = document.createElement('div');
            noImage.className = 'no-image-placeholder';
            noImage.textContent = 'Sin imagen';
            mediaDiv.appendChild(noImage);
        }

        article.appendChild(mediaDiv);

        // Cuerpo de la tarjeta
        const bodyDiv = document.createElement('div');
        bodyDiv.className = 'card__body';

        // Título
        const titleDiv = document.createElement('div');
        titleDiv.className = 'card__title';
        titleDiv.textContent = producto.izenburua;
        bodyDiv.appendChild(titleDiv);

        // Meta información
        const metaDiv = document.createElement('div');
        metaDiv.className = 'card__meta';

        if (producto.artista) {
            const span = document.createElement('span');
            span.textContent = producto.artista;
            metaDiv.appendChild(span);
        }
        if (producto.urtea) {
            const span = document.createElement('span');
            span.textContent = producto.urtea;
            metaDiv.appendChild(span);
        }
        if (producto.categoria_nombre) {
            const span = document.createElement('span');
            span.textContent = producto.categoria_nombre;
            metaDiv.appendChild(span);
        }

        bodyDiv.appendChild(metaDiv);

        // Precio
        const priceDiv = document.createElement('div');
        priceDiv.className = 'card__price';
        priceDiv.textContent = `${Number(producto.prezioa).toFixed(2)} €`;
        bodyDiv.appendChild(priceDiv);

        article.appendChild(bodyDiv);

        // Footer con botón de favorito
        const footerDiv = document.createElement('div');
        footerDiv.className = 'card__footer';

        // Botón de favoritos
        const favBtn = document.createElement('button');
        favBtn.className = 'btn-favorite';
        favBtn.dataset.productId = producto.id;
        favBtn.setAttribute('aria-label', 'Añadir a favoritos');
        favBtn.innerHTML = `
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
            </svg>
        `;
        // Aplicar estado inicial del corazón según favoritos
        const isFav = window.SoinutrukFavorites && window.SoinutrukFavorites.isFavorite(producto.id);
        if (isFav) {
            favBtn.classList.add('btn-favorite--active');
            favBtn.querySelector('svg').setAttribute('fill', 'currentColor');
        }
        footerDiv.appendChild(favBtn);

        // Botón de ver detalle
        const buyBtn = document.createElement('span');
        buyBtn.className = 'card__buy';
        buyBtn.textContent = 'Ver detalle';
        footerDiv.appendChild(buyBtn);

        article.appendChild(footerDiv);

        return article;
    }

    /**
     * Adjuntar navegación por delegación a tarjetas
     * @param {HTMLElement} container - Contenedor del grid
     */
    function attachCardNavigation(container) {
        container.addEventListener('click', (e) => {
            const card = e.target.closest('article[data-url]');
            if (!card) return;
            if (e.target.closest('.btn-favorite')) return;
            window.location.href = card.dataset.url;
        });
    }

    /**
     * Crear controles de paginación
     * @param {Object} paginacion - Datos de paginación
     * @param {Function} onPageChange - Callback para cambiar de página
     * @returns {HTMLElement} Contenedor de paginación
     */
    function createPagination(paginacion, onPageChange) {
        const container = document.createElement('div');
        container.className = 'pagination';

        const { pagina, paginas, total } = paginacion;

        // Botón anterior
        if (pagina > 1) {
            const prevBtn = document.createElement('button');
            prevBtn.textContent = '← Anterior';
            prevBtn.className = 'pagination-btn';
            prevBtn.onclick = () => onPageChange(pagina - 1);
            container.appendChild(prevBtn);
        }

        // Números de páginas
        const start = Math.max(1, pagina - 2);
        const end = Math.min(paginas, pagina + 2);

        for (let i = start; i <= end; i++) {
            const btn = document.createElement('button');
            btn.textContent = i;
            btn.className = 'pagination-btn pagination-btn--page';
            if (i === pagina) {
                btn.classList.add('pagination-btn--active');
            } else {
                btn.onclick = () => onPageChange(i);
            }
            container.appendChild(btn);
        }

        // Botón siguiente
        if (pagina < paginas) {
            const nextBtn = document.createElement('button');
            nextBtn.textContent = 'Siguiente →';
            nextBtn.className = 'pagination-btn';
            nextBtn.onclick = () => onPageChange(pagina + 1);
            container.appendChild(nextBtn);
        }

        return container;
    }

    /**
     * Obtener valores actuales de búsqueda y categoría
     */
    function getCurrentSearch() {
        const searchInput = document.getElementById('searchInput');
        return searchInput ? searchInput.value.trim() : '';
    }

    function getCurrentCategory() {
        const categorySelect = document.getElementById('categoryFilter');
        return categorySelect ? parseInt(categorySelect.value, 10) || 0 : 0;
    }

    /**
     * Aplicar filtros y actualizar catálogo con paginación
     * @param {string} q - Término de búsqueda
     * @param {number} kategoriaId - ID de categoría
     * @param {number} page - Página a cargar
     * @param {number} limit - Límite por página
     */
    async function applyFilters(q, kategoriaId, page, limit) {
        const grid = document.getElementById('catalogGrid');
        if (!grid) return;

        // Actualizar estado global
        currentPage = page;
        currentLimit = limit;

        // Mostrar estado de carga
        grid.innerHTML = `
            <div class="grid-message grid-message--loading">
                Buscando página ${page}...
            </div>
        `;

        // Obtener productos filtrados con paginación
        const result = await fetchProductos(q, kategoriaId, page, limit);

        // Renderizar resultados
        renderProductos(result.productos, result.paginacion, grid);

        // Agregar controles de paginación
        const paginationContainer = document.getElementById('paginationContainer');
        if (paginationContainer) {
            paginationContainer.innerHTML = '';
            if (result.paginacion.paginas > 1) {
                const pagination = createPagination(result.paginacion, (newPage) => {
                    const searchVal = getCurrentSearch();
                    const catVal = getCurrentCategory();
                    applyFilters(searchVal, catVal, newPage, currentLimit);
                });
                paginationContainer.appendChild(pagination);
            }
        }

        // Actualizar contador de resultados
        updateResultCount(result.paginacion.total);
    }

    /**
     * Actualizar contador visual de resultados (si existiera un elemento)
     */
    function updateResultCount(total) {
        // Se puede expandir para mostrar "X resultados" si se desea
        console.debug(`Results: ${total} found`);
    }

    /**
     * Manejador con debounce para búsqueda
     * Evita excesivas peticiones mientras el usuario escribe
     */
    function handleSearchInput() {
        const searchInput = document.getElementById('searchInput');
        const categorySelect = document.getElementById('categoryFilter');

        if (!searchInput || !categorySelect) return;

        const q = searchInput.value.trim();
        const kategoriaId = parseInt(categorySelect.value, 10) || 0;

        // Cancelar timeout anterior
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        // Aplicar filtros con debounce
        searchTimeout = setTimeout(() => {
            applyFilters(q, kategoriaId, 1, currentLimit);
        }, DEBOUNCE_DELAY);
    }

    /**
     * Inicializar módulo de búsqueda
     * Configura eventos en input de búsqueda y selector de categorías
     */
    function init() {
        const searchInput = document.getElementById('searchInput');
        const categorySelect = document.getElementById('categoryFilter');

        if (searchInput) {
            searchInput.addEventListener('input', handleSearchInput);
        }

        if (categorySelect) {
            categorySelect.addEventListener('change', handleSearchInput);
        }

        // Cargar productos iniciales
        const grid = document.getElementById('catalogGrid');
        if (grid) {
            applyFilters('', 0, 1, currentLimit);
        }
    }

    // Exportar funciones globales para uso externo
    window.SoinutrukSearch = {
        applyFilters,
        currentPage,
        currentLimit,
        fetchProductos
    };

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Función para actualizar paginación desde fuera
    window.updatePagination = (page, limit) => {
        const searchInput = document.getElementById('searchInput');
        const categorySelect = document.getElementById('categoryFilter');
        const q = searchInput ? searchInput.value.trim() : '';
        const kategoriaId = categorySelect ? parseInt(categorySelect.value, 10) || 0 : 0;
        applyFilters(q, kategoriaId, page, limit);
    };
})();