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

const item__remove = document.querySelectorAll('item__remove');
item__remove.addEventListener("click", function () {
removeItem();
});