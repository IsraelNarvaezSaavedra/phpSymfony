<?php
namespace App\Message;

class GenerarRespuestaIA {
    public function __construct(
        public string $callSid,
        public string $textoUsuario,
        public string $telefono
    ) {}
}