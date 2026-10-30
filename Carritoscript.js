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
item__remove.addEventListener("click", function () {
removeItem();
});
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
        borrar.addEventListener("click", {} => eraseitem(product.id));
        line.append(text, borrar);
        cart__items.appendChild(line);
    });
    
}