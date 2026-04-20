function eliminarFila(boton) {
    let bloque = boton.closest('.opcion_bloque');
    if (bloque) {
        bloque.remove();
    }
}


document.addEventListener('DOMContentLoaded', function() {
//Tipo de persona que llama
let radioAnonimo = document.querySelector('.radio_anonimo');
let radioNoClientes = document.querySelector('.radio_no_clientes');
let radioClientes = document.querySelector('.radio_clientes');
let anonimoBtn = document.getElementById('anonimo');
let noClientesBtn = document.getElementById('no_clientes');
let clientesBtn = document.getElementById('clientes');

if (radioAnonimo && radioNoClientes && radioClientes && anonimoBtn && noClientesBtn && clientesBtn) {
    anonimoBtn.addEventListener('click', function() {
        radioAnonimo.checked = true;
    });
    noClientesBtn.addEventListener('click', function() {
        radioNoClientes.checked = true;
    });
    clientesBtn.addEventListener('click', function() {
        radioClientes.checked = true;
    });
}

//Opcion de respuesta, si prefiere IVA o IVR
let radioTeclado = document.querySelector('.radio_teclado');
let radioIA = document.querySelector('.radio_ia');
let iaBtn = document.getElementById('ia');
let tecladoBtn = document.getElementById('teclado');
let iaPrompt = document.getElementById('iaPrompt');
let formularioOpciones = document.getElementById('formularioOpciones');
let agregarOpcionBtn = document.getElementById('agregar_opcion');


function syncTipoInteraccion(boton){
    if (boton.id === 'ia') {
        //Seleccionar el radio de IA y deseleccionar el de teclado
        radioIA.checked = true;
        radioTeclado.checked = false;
        TIPO_ACTUAL = 'ia';
        //Mostrar el prompt de IA y ocultar el formulario de opciones
        iaPrompt.classList.remove('d-none');
        formularioOpciones.classList.add('d-none');
        //Actualizar clases visuales de los botones
        iaBtn.classList.add('active');
        tecladoBtn.classList.remove('active');
    } else if (boton.id === 'teclado') {
        //Seleccionar el radio de teclado y deseleccionar el de IA
        radioTeclado.checked = true;
        radioIA.checked = false;
        TIPO_ACTUAL = 'teclado';
        //Mostrar el formulario de opciones y ocultar el prompt de IA
        formularioOpciones.classList.remove('d-none');
        iaPrompt.classList.add('d-none');
        //Actualizar clases visuales de los botones
        tecladoBtn.classList.add('active');
        iaBtn.classList.remove('active');
    }
}

if ( radioIA && radioTeclado && iaBtn && tecladoBtn && iaPrompt && formularioOpciones) {
    iaBtn.addEventListener('click', function() {
        syncTipoInteraccion(iaBtn);
    });
    tecladoBtn.addEventListener('click', function() {
        syncTipoInteraccion(tecladoBtn);
    });
}

//Agregar opciones (mismo nivel/hermanos)
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

//Agregar submenús (hijos, 3 maximo)
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

// Inicializar el estado de los desplegarbles al cargar la página
document.querySelectorAll('.desplegable').forEach(desplegable => {
    const evento = new Event('change', { bubbles: true });
    desplegable.dispatchEvent(evento);
});
});