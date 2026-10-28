// Perfil - gestión de paneles, formulario de venta y edición de artículos
document.addEventListener('DOMContentLoaded', () => {

    // ---------- Navegación entre paneles ----------
    document.querySelectorAll('.nav__btn[data-target]').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.target;
            document.querySelectorAll('.nav__btn[data-target]').forEach(b => b.classList.remove('nav__btn--active'));
            btn.classList.add('nav__btn--active');
            document.querySelectorAll('.panel').forEach(panel => {
                panel.classList.remove('panel--active');
            });
            document.getElementById(target).classList.add('panel--active');

            // Si se abre el panel de vender, resetear formulario a modo crear
            if (target === 'sell') {
                resetForm();
            }
        });
    });

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
        document.getElementById('currentImagePreview').style.display = 'none';
        conditionals.forEach(el => { el.style.display = 'none'; });
        editingId = null;
    }

    // ---------- Mostrar campos condicionales según categoría ----------
    categorySelect?.addEventListener('change', () => {
        const val = categorySelect.value;
        conditionals.forEach(el => {
            el.style.display = el.dataset.condition === val ? 'block' : 'none';
        });
    });

    // ---------- Abrir panel "Vender" sin resetear ----------
    function openSellPanel() {
        document.querySelectorAll('.nav__btn[data-target]').forEach(b => b.classList.remove('nav__btn--active'));
        document.querySelector('.nav__btn[data-target="sell"]')?.classList.add('nav__btn--active');
        document.querySelectorAll('.panel').forEach(panel => panel.classList.remove('panel--active'));
        document.getElementById('sell').classList.add('panel--active');
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
                    document.getElementById('currentImagePreview').style.display = 'block';
                } else {
                    document.getElementById('currentImagePreview').style.display = 'none';
                }

                // Activar campos condicionales según categoría
                const catSelect = document.getElementById('category');
                const val = catSelect.value;
                conditionals.forEach(el => {
                    el.style.display = el.dataset.condition === val ? 'block' : 'none';
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
                document.getElementById('sell').scrollIntoView({ behavior: 'smooth', block: 'start' });

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
