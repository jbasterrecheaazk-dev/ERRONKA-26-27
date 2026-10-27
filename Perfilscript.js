        // Cambiar paneles
        document.querySelectorAll('.nav__btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const target = btn.dataset.target;
                document.querySelectorAll('.nav__btn').forEach(b => b.classList.remove('nav__btn--active'));
                btn.classList.add('nav__btn--active');
                document.querySelectorAll('.panel').forEach(panel => {
                    panel.classList.remove('panel--active');
                });
                document.getElementById(target).classList.add('panel--active');
            });
        });