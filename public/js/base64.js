document.addEventListener('DOMContentLoaded', function () {
    //copiar base64
    let base64 = document.getElementById('b64completo');
    let boton = document.getElementById('btnCopiar');

    //en caso de error
    let desplegar = document.getElementById('desplegar');
    let texto = document.getElementById('textaco');

    // previsualizar img
    let img = document.getElementById('coche_fotoCoche');
    let previsualizar = document.getElementById('previsualizar');

    if (boton && base64) {
        //copiar base64
        boton.addEventListener('click', function(){
            navigator.clipboard.writeText(base64.innerText)
            .then(()=> alert('Texto copiado correctamente'))
            .catch(err => console.error("Error al copiar: ", err));
        });
    }

    if (desplegar && texto) {
        //en caso de error
        desplegar.addEventListener('click', function(){
            texto.classList.toggle('d-none');    
        });
    }
    
    if(img){
        //previsualizar img
        img.addEventListener('change', function(e){
            previsualizar.innerHTML='';
            let crearImg = document.createElement('img');
            let br1 = document.createElement('br');
            let br2 = document.createElement('br');

            crearImg.classList.add("card-img-top", "border", "rounded");
            let archivo = e.target.files[0];
            let tempUrl = URL.createObjectURL(archivo);
            crearImg.src = tempUrl;
            previsualizar.appendChild(crearImg);
            previsualizar.appendChild(br1);
            previsualizar.appendChild(br2);
        })
    }
});