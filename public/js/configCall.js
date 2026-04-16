let ia = document.getElementById('ia');
let teclado = document.getElementById('teclado');
let iaPrompt = document.getElementById('iaPrompt');
let formularioOpciones = document.getElementById('formularioOpciones');

ia.addEventListener('click', function() {
    iaPrompt.classList.remove('d-none');
    formularioOpciones.classList.add('d-none');
});

teclado.addEventListener('click', function() {
    formularioOpciones.classList.remove('d-none');
    iaPrompt.classList.add('d-none');
});