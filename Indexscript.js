/*const productos = document.getElementById('productos');
const products__head = document.querySelectorAll('.products__head');
const products__filter = document.querySelectorAll('.products__filter');
const Cart_button = document.getElementById('.cart-button');
Cart_button.addEventListener('click', () => {
    const cart__items = document.querySelectorAll('.cart__items');
    cart__items.hidden = false;
    window.open('carrito.html');
});
const card = document.querySelector('.card');

card.addEventListener('click', () => {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
function gordeCarrito() {
    localStorage.setItem('cart', JSON.stringify(cart));
}
function addCart(id, izenburua, prezioa) {
    const badagoenProduktua = cart.find(item => item.id === id);
    if (badagoenProduktua) {
        alert('El producto seleccionado ya esta en el carrito.');
        //badagoenProduktua.amount += 1;
    } else {
        cart.push({
            id: id,
            izenburua: izenburua,
            prezioa: prezioa,
            amount: 1
        });
    }
    gordeCarrito();
    aldaketakEzarri();
}
function aldaketakEzarri() {
    console.log("Cesta actual: ",cart)
}
});
*/
let cartList = [];

function sartuProduktua(izenburua, prezioa) {
    const index
}