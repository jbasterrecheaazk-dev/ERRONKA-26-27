// Perfil - gestión de paneles, formulario de venta y edición de artículos
document.addEventListener('DOMContentLoaded', () => {

    // ---------- Utilidad: cambiar panel activo ----------
    function switchPanel(target) {
        document.querySelectorAll('.nav__btn[data-target]').forEach(b => b.classList.remove('nav__btn--active'));
        const btn = document.querySelector(`.nav__btn[data-target="${target}"]`);
        if (btn) btn.classList.add('nav__btn--active');
        document.querySelectorAll('.panel').forEach(p => p.classList.remove('panel--active'));
        const panel = document.getElementById(target);
        if (panel) panel.classList.add('panel--active');
    }

    // ---------- Navegación entre paneles (botones del nav) ----------
    document.querySelectorAll('.nav__btn[data-target]').forEach(btn => {
        btn.addEventListener('click', () => switchPanel(btn.dataset.target));
    });

    // ---------- Navegación por hash (ej: /perfil#sell) ----------
    function handleHash() {
        const hash = window.location.hash?.slice(1);
        if (!hash) return;
        // /perfil#sell → panel-sell
        const target = hash === 'sell' ? 'panel-sell' : hash;
        // Si el panel existe, abrirlo; si no, ignorar
        if (document.getElementById(target)) {
            switchPanel(target);
            if (target === 'panel-sell') resetForm();
        }
    }
    // Ejecutar after one frame (el browser habrá procesado el scroll nativo)
    requestAnimationFrame(() => { handleHash(); });
    window.addEventListener('hashchange', handleHash);

    // ---------- Variables globales del formulario ----------
    const form             = document.getElementById('productForm');
    const categorySelect   = document.getElementById('category');
    const conditionals     = document.querySelectorAll('.form__conditional');

    // ---------- Resetear formulario a modo crear ----------
    function resetForm() {
        form.reset();
        document.getElementById('editId').value = '';
        document.getElementById('formTitle').textContent = 'Publicar artículo en venta';
        document.getElementById('submitBtn').textContent = 'Publicar anuncio';
        document.getElementById('currentImagePreview').classList.remove('image-preview--visible');
        conditionals.forEach(el => { el.classList.remove('form__conditional--visible'); });
    }

    // ---------- Mostrar campos condicionales según categoría ----------
    categorySelect?.addEventListener('change', () => {
        const val = categorySelect.value;
        conditionals.forEach(el => {
            if (el.dataset.condition === val) {
                el.classList.add('form__conditional--visible');
            } else {
                el.classList.remove('form__conditional--visible');
            }
        });
    });

    // ---------- Abrir panel "Vender" para editar ----------
    function openSellPanel() {
        switchPanel('panel-sell');
    }

    // ---------- Cargar datos del producto para editar ----------
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const id = btn.dataset.productId;
            if (!id) return;

            try {
                const res = await fetch('/api/producto?id=' + encodeURIComponent(id));
                if (!res.ok) throw new Error('No autorizado');
                const p = await res.json();

                openSellPanel();

                document.getElementById('editId').value = p.id;
                document.getElementById('formTitle').textContent = 'Editar artículo';
                document.getElementById('submitBtn').textContent = 'Guardar cambios';
                document.getElementById('category').value = String(p.kategoria_id);
                document.getElementById('title').value = p.izenburua || '';
                document.getElementById('price').value = p.prezioa || '';
                document.getElementById('description').value = p.deskribapena || '';

                if (p.irudia) {
                    document.getElementById('currentImage').src = '/' + p.irudia;
                    document.getElementById('currentImagePreview').classList.add('image-preview--visible');
                } else {
                    document.getElementById('currentImagePreview').classList.remove('image-preview--visible');
                }

                // Activar campos condicionales según categoría
                const catSelect = document.getElementById('category');
                const val = catSelect.value;
                conditionals.forEach(el => {
                    if (el.dataset.condition === val) {
                        el.classList.add('form__conditional--visible');
                    } else {
                        el.classList.remove('form__conditional--visible');
                    }
                });

                // Rellenar campos condicionales
                const selectedCond = document.querySelector(`.form__conditional[data-condition="${val}"]`);
                if (selectedCond) {
                    const artistInput = selectedCond.querySelector('input[name="artista"]');
                    const yearInput   = selectedCond.querySelector('input[name="urtea"]');
                    if (artistInput) artistInput.value = p.artista || '';
                    if (yearInput)   yearInput.value   = p.urtea  || '';
                }

                document.getElementById('panel-sell').scrollIntoView({ behavior: 'smooth', block: 'start' });

            } catch (err) {
                const tempErr = document.createElement('div');
                tempErr.className = 'form__feedback form__feedback--error';
                tempErr.textContent = 'Error al cargar: ' + err.message;
                form.parentElement.insertBefore(tempErr, form);
                setTimeout(() => { tempErr.remove(); }, 5000);
            }
        });
    });

    // ---------- Navegación por delegación en tarjetas de mis artículos ----------
    const misArticulosGrid = document.getElementById('misArticulosGrid');
    misArticulosGrid?.addEventListener('click', (e) => {
        const card = e.target.closest('article[data-url]');
        if (!card) return;
        if (e.target.closest('.edit-btn')) return;
        window.location.href = card.dataset.url;
    });

    // ---------- Cargar favoritos ----------
    async function loadFavorites() {
        const favoritesPanel = document.getElementById('favorites');
        if (!favoritesPanel) return;

        const favoriteIds = window.SoinutrukFavorites?.getFavorites?.() || [];
        const grid = document.getElementById('favoritesGrid');
        const emptyMsg = document.getElementById('favoritesEmpty');

        if (!grid) return;

        if (!favoriteIds.length) {
            grid.innerHTML = '';
            if (emptyMsg) emptyMsg.style.display = 'block';
            return;
        }

        if (emptyMsg) emptyMsg.style.display = 'none';

        try {
            const idsParam = favoriteIds.join(',');
            const res = await fetch(`/api/productos?ids=${encodeURIComponent(idsParam)}`);
            if (!res.ok) throw new Error('No se pudieron cargar los favoritos');
            const productos = await res.json();

            grid.innerHTML = '';
            productos.forEach(p => {
                const article = document.createElement('article');
                article.className = 'card';
                article.dataset.url = `/producto?id=${p.id}`;

                const media = document.createElement('div');
                media.className = 'card__media';
                if (p.irudia) {
                    const img = document.createElement('img');
                    img.src = '/' + p.irudia;
                    img.alt = p.izenburua;
                    img.loading = 'lazy';
                    media.appendChild(img);
                } else {
                    media.innerHTML = '<div class="card__media--empty">Sin imagen</div>';
                }
                article.appendChild(media);

                const body = document.createElement('div');
                body.className = 'card__body';

                const title = document.createElement('div');
                title.className = 'card__title';
                title.textContent = p.izenburua;
                body.appendChild(title);

                const meta = document.createElement('div');
                meta.className = 'card__meta';
                if (p.artista) {
                    const span = document.createElement('span');
                    span.textContent = p.artista;
                    meta.appendChild(span);
                }
                if (p.urtea) {
                    const span = document.createElement('span');
                    span.textContent = p.urtea;
                    meta.appendChild(span);
                }
                body.appendChild(meta);

                const price = document.createElement('div');
                price.className = 'card__price';
                price.textContent = `${Number(p.prezioa).toFixed(2)} €`;
                body.appendChild(price);

                article.appendChild(body);

                const footer = document.createElement('div');
                footer.className = 'card__footer';

                const favBtn = document.createElement('button');
                favBtn.className = 'btn-favorite btn-favorite--active';
                favBtn.dataset.productId = p.id;
                favBtn.setAttribute('aria-label', 'Quitar de favoritos');
                favBtn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                `;
                footer.appendChild(favBtn);

                const buyBtn = document.createElement('span');
                buyBtn.className = 'card__buy';
                buyBtn.textContent = 'Ver detalle';
                footer.appendChild(buyBtn);

                article.appendChild(footer);
                grid.appendChild(article);
            });

            // Delegación de click en favoritos
            grid.addEventListener('click', (e) => {
                const card = e.target.closest('article[data-url]');
                if (!card) return;
                const favBtn = e.target.closest('.btn-favorite');
                if (favBtn) {
                    e.preventDefault();
                    // El evento global de favoritos.js se encargará de quitarlo
                    favBtn.click();
                    // Recargar en 300ms
                    setTimeout(loadFavorites, 300);
                    return;
                }
                window.location.href = card.dataset.url;
            });
        } catch (err) {
            console.error('Error cargando favoritos:', err);
            grid.innerHTML = '<p style="color:var(--muted)">No se pudieron cargar los favoritos.</p>';
        }
    }

    // Cargar favoritos cuando se abre el panel
    document.querySelectorAll('.nav__btn[data-target="favorites"]').forEach(btn => {
        btn.addEventListener('click', () => {
            setTimeout(loadFavorites, 50);
        });
    });

    // Cargar también si se entra por hash
    const originalSwitchPanel = switchPanel;
    switchPanel = function(target) {
        originalSwitchPanel(target);
        if (target === 'favorites') {
            setTimeout(loadFavorites, 50);
        }
    };
});
