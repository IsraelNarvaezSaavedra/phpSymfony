const longitud = document.getElementById('longitud');
const mayus = document.getElementById('mayuscula');
const simbolo = document.getElementById('simbolo');
const num = document.getElementById('num');
const campoFormulario = document.getElementsByClassName('campo')[0];
const lista = document.getElementById('lista');
const p = document.getElementById('p');

function rojo(c) {
    if (!c) return;
    c.classList.remove('text-success');
    c.classList.add('text-danger');
}
function verde(c) {
    if (!c) return;
    c.classList.remove('text-danger');
    c.classList.add('text-success');
}
function mostrarLista(p, div) {
    if (!p || !div) return;
    div.classList.remove('d-none');
    div.classList.add("d-block");
    p.classList.remove('d-block');
    p.classList.add("d-none");
}
function mostrarTexto(p, div) {
    if (!p || !div) return;
    p.classList.remove('d-none');
    p.classList.add("d-block");
    div.classList.remove('d-block');
    div.classList.add("d-none");
}
if (campoFormulario) {
campoFormulario.addEventListener('input', function(){
    let valorCampo = campoFormulario.value;
    if (valorCampo.length >=6 ) {
        verde(longitud);
    }else{
        rojo(longitud);
    }

    if (/[A-Z]/.test(valorCampo)) {
        verde(mayus);
    } else {
        rojo(mayus);
    }

    if (/[!¡¿?*+&%$.,]/.test(valorCampo)) {
        verde(simbolo);
    } else {
        rojo(simbolo);
    }

    if (/[0-9]/.test(valorCampo)) {
        verde(num);
    } else {
        rojo(num);
    }

    if (valorCampo.length > 0) {
        mostrarLista(p, lista);

    } else {
        mostrarTexto(p, lista);
    }
});
}