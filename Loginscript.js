        // Cambiar pestañas
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.dataset.target;
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('tab--active'));
                tab.classList.add('tab--active');
                document.querySelectorAll('.form').forEach(form => {
                    form.classList.remove('form--active');
                });
                document.getElementById(target).classList.add('form--active');
            });
        });