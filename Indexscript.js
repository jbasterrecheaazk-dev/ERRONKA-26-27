const productos = document.getElementById('productos');
const products__head = document.querySelectorAll('.products__head');
const products__filter = document.querySelectorAll('.products__filter');
const Cart_button = document.querySelector('.header__icon-btn');
Cart_button.addEventListener('click', () => {
    const cart__items = document.querySelectorAll('.cart__items');
    cart__items.hidden = false;
});