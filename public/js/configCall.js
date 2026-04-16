function eliminarFila(boton) {
    let bloque = boton.closest('.opcion_bloque');
    if (bloque) {
        bloque.remove();
    }
}

window.onload = function() {

//Tipo de persona que llama
let tipoLlamadaSelect = document.querySelector('.tipo_llamada');
let anonimoBtn = document.getElementById('anonimo');
let noClientesBtn = document.getElementById('no_clientes');
let clientesBtn = document.getElementById('clientes');

if (tipoLlamadaSelect && anonimoBtn && noClientesBtn && clientesBtn) {
    anonimoBtn.addEventListener('click', function() {
        tipoLlamadaSelect.value = 'anonimo';
    });
    noClientesBtn.addEventListener('click', function() {
        tipoLlamadaSelect.value = 'no_cliente';
    });
    clientesBtn.addEventListener('click', function() {
        tipoLlamadaSelect.value = 'cliente';
    });
}

//Opcion de respuesta, si prefiere IVA o IVR
let tipoInteraccionSelect = document.querySelector('.tipo_interaccion');
let iaBtn = document.getElementById('ia');
let tecladoBtn = document.getElementById('teclado');

if (tipoInteraccionSelect && iaBtn && tecladoBtn) {
    iaBtn.addEventListener('click', function() {
        tipoInteraccionSelect.value = 'ia';
    });
    tecladoBtn.addEventListener('click', function() {
        tipoInteraccionSelect.value = 'teclado';
    });
}

//Mostrar formulario IA o teclado
let iaPrompt = document.getElementById('iaPrompt');
let formularioOpciones = document.getElementById('formularioOpciones');
let agregarOpcionBtn = document.getElementById('agregar_opcion');

if (iaBtn && iaPrompt && formularioOpciones && tecladoBtn) {
    iaBtn.addEventListener('click', function() {
        iaPrompt.classList.remove('d-none');
            formularioOpciones.classList.add('d-none');
    });

    tecladoBtn.addEventListener('click', function() {
        formularioOpciones.classList.remove('d-none');
        iaPrompt.classList.add('d-none');
    });   
}

//Agregar opciones al formulario de teclado
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

//Agregar opciones a los submenú
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

//Desplegable que segun lo que escojas, te muestra un formulario u otro 
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