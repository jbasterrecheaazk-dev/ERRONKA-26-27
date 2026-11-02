fetch ('file:///C:/Users/j_basterrecheaazk/Desktop/Erronka/carrito.html')
.then(result => result.json())
.then(data => {
    console.log("Auek dira zuk aukeratutako produktuak:", data);
})

fetch ('file:///C:/Users/j_basterrecheaazk/Desktop/Erronka/carrito.html', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        /*
        id: "3", 
    izena: "Pantaila LED", 
    prezioa: 150
    */
    })
});

fetch ('file:///C:/Users/j_basterrecheaazk/Desktop/Erronka/carrito.html', {
    method: 'PATCH',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        /*
        prezioa: 30
        */
    })
});

fetch('file:///C:/Users/j_basterrecheaazk/Desktop/Erronka/carrito.html', {
    method: 'DELETE'
});

const cart__items = document.querySelectorAll('cart__items');
const item__remove = document.querySelectorAll('item__remove');
const carritoBtn = document.querySelectorAll('header__icon-btn');
const item__content = document.querySelectorAll('item__content');
const close = document.querySelectorAll('header__brand');
const erosi = document.querySelectorAll('btn btn--primary');
const guztira = document.querySelectorAll('summary__total');
function erakutsiLista() {
    cart__items.innerHTML = '';
    let result = 0;
    cart__items.forEach(product => {
        result += product.price;
        const line = document.createElement('p');
        const text = document.createElement('span');
        text.textContent = `${product.name} - ${product.price} € `;
        const  borrar = document.createElement('button');
        borrar.textContent = 'Ezabatu';
        borrar.addEventListener("click", () => eraseitem(product.id));
        line.append(text, borrar);
        cart__items.appendChild(line);
    });
    cartresult.textContent = `Guztira: ${result.toFixed(2)} €`;
}

carritoBtn.addEventListener('click', () => {
    erakutsiLista();
    cart__items.hidden = false;
});
close.addEventListener('click', () => {
cart__items.hidden = true;
});
function ezabatuProduktua(id) {
    const index = cart__items.findIndex(p => p.id === id);
    if (index !== -1) {
        cart__items.splice(index, 1);
    }
    erakutsiLista();
}
erosi.addEventListener('click', () => {
    if (cart__items.length === 0) {
        alert('Lista hutsik dago.')
        return;
    }
    alert('Erosketa operazioa ondo egin da!');
    cart__items.splice(0, cart__items.length);
    erakutsiLista();
    cart__items.hidden = true;
});