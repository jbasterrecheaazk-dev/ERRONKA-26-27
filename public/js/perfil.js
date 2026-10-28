// Perfil - gestión de paneles, formulario de venta y edición de artículos
document.addEventListener('DOMContentLoaded', () => {

    // ---------- Fijar posición de la sidebar ----------
    let sidebarFixed = false;
    const sidebar = document.getElementById('profileSidebar');
    const placeholder = document.getElementById('sidebarPlaceholder');

    function fixSidebarPosition() {
        if (!sidebar || !placeholder) return;

        // Si ya está fijada, solo actualizar dimensiones si es necesario
        if (sidebarFixed) {
            const rect = sidebar.getBoundingClientRect();
            placeholder.style.width = rect.width + 'px';
            return;
        }

        // Obtener posición y dimensiones actuales
        const rect = sidebar.getBoundingClientRect();

        // Crear placeholder con las mismas dimensiones
        placeholder.style.width = rect.width + 'px';
        placeholder.style.height = rect.height + 'px';

        // Fijar la sidebar
        sidebar.style.position = 'fixed';
        sidebar.style.top = '80px';
        sidebar.style.left = rect.left + 'px';
        sidebar.style.width = rect.width + 'px';
        sidebar.style.zIndex = '100';

        sidebarFixed = true;
    }

    // Ejecutar cuando carga la página
    requestAnimationFrame(fixSidebarPosition);

    // Reajustar al cambiar el tamaño de la ventana
    window.addEventListener('resize', fixSidebarPosition);

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
    let editingId = null;

    // ---------- Resetear formulario a modo crear ----------
    function resetForm() {
        form.reset();
        document.getElementById('editId').value = '';
        document.getElementById('formTitle').textContent = 'Publicar artículo en venta';
        document.getElementById('submitBtn').textContent = 'Publicar anuncio';
        document.getElementById('currentImagePreview').classList.remove('image-preview--visible');
        conditionals.forEach(el => { el.classList.remove('form__conditional--visible'); });
        editingId = null;
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
                    const condSelect  = selectedCond.querySelector('select[name="egoera"]');
                    if (artistInput) artistInput.value = p.artista || '';
                    if (yearInput)   yearInput.value   = p.urtea  || '';
                    if (condSelect)  condSelect.value  = p.egoera || 'Mint';
                }

                editingId = p.id;
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
});
