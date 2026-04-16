function eliminarFila(boton) {
    let bloque = boton.closest('.opcion_bloque');
    if (bloque) {
        bloque.remove();
    }
}

window.onload = function() {
let ia = document.getElementById('ia');
let teclado = document.getElementById('teclado');
let iaPrompt = document.getElementById('iaPrompt');
let formularioOpciones = document.getElementById('formularioOpciones');
let agregarOpcionBtn = document.getElementById('agregar_opcion');

if (ia && iaPrompt && formularioOpciones && teclado) {
    ia.addEventListener('click', function() {
        iaPrompt.classList.remove('d-none');
            formularioOpciones.classList.add('d-none');
    });

    teclado.addEventListener('click', function() {
        formularioOpciones.classList.remove('d-none');
        iaPrompt.classList.add('d-none');
    });   
}
if (agregarOpcionBtn) {
    agregarOpcionBtn.addEventListener('click', function() {
        const contenedor = document.getElementById('contenedor_coleccion');
        if (contenedor) {
            const prototype = contenedor.dataset.prototype;
            const index = parseInt(contenedor.dataset.index);
            if (prototype !== undefined && !isNaN(index)) {
                const nuevaFila = prototype.replace(/__name__/g, index);
                contenedor.dataset.index = index + 1;
                if (nuevaFila) {
                    contenedor.insertAdjacentHTML('beforeend', nuevaFila);
                }
            }
        }
    });
}

document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('agregar_subopcion')) {
        const contenedor = e.target.closest('.submenu_contenedor').querySelector('.subcontenedor_molde');
        const prototype = contenedor.dataset.prototype;
        const index = parseInt(contenedor.dataset.index);
        const match = prototype.match(/__name\d+__/);
        if (match) {
            const marcador = match[0];
            const nuevaFila = prototype.replaceAll(marcador, index);
            contenedor.dataset.index = index + 1;
            if (nuevaFila) {
                contenedor.insertAdjacentHTML('beforeend', nuevaFila);
            }
        }
    }
});

//Desplegable
document.addEventListener('change', function(e) {
    if (e.target && e.target.classList.contains('desplegable')) {
        const bloque = e.target.closest('.opcion_bloque');
        if (bloque) {
            const valor = e.target.value;
            const numeroAgente = bloque.querySelector('.numeroAgente');
            const mensajePersonalizado = bloque.querySelector('.mensajePersonalizado');
            const submenu = bloque.querySelector('.submenu_contenedor');
            if (valor=== 'submenu' && !submenu) {
                alert("Límite alcanzado. No se pueden crear más niveles de submenú (Máximo 3)");
                e.target.value = '';
            }
            if (numeroAgente) numeroAgente.classList.add('d-none');
            if (mensajePersonalizado) mensajePersonalizado.classList.add('d-none');
            if (submenu) submenu.classList.add('d-none');
            
            if (valor === 'submenu' && submenu) {
                submenu.classList.remove('d-none');
            } else if (valor === 'mensaje' && mensajePersonalizado) {
                mensajePersonalizado.classList.remove('d-none');
            } else if (valor === 'transferir' && numeroAgente) {
                numeroAgente.classList.remove('d-none');
            }
        }
    }
});
};