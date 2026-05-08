<?php

namespace App\MessageHandler;

use App\Entity\Mensaje;
use App\Enum\TipoInteraccion;
use App\Enum\TipoUsuario;
use App\Message\GenerarRespuestaIA;
use App\Repository\ConfiguracionLlamadaRepository;
use App\Repository\MensajeRepository;
use App\Repository\UsuarioRepository;
use App\Service\IAService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RespuestaIAHandler
{
    public function __construct(
        private IAService $ia,
        private EntityManagerInterface $em,
        private UsuarioRepository $ur,
        private ConfiguracionLlamadaRepository $configRepo,
        private MensajeRepository $mr
    ) {
    }

    public function __invoke(GenerarRespuestaIA $mensaje)
    {
        if (empty(trim($mensaje->textoUsuario)) || $mensaje->textoUsuario === '(Sin respuesta)') {
            return;
        }

        $respuestaExistente = $this->mr->findOneBy([
            'callSid' => $mensaje->callSid,
            'rol' => TipoInteraccion::IA->value
        ]);

        if ($respuestaExistente) {
            return;
        }

        $llamada = $this->mr->findOneBy(['callSid' => $mensaje->callSid]);

        if ($llamada) {
            $this->em->refresh($llamada);
            if ($llamada && $llamada->isFinalizado()) {
                return;
            }
        }
        $conversacion = $this->mr->getUltimosMensajes($mensaje->telefono);
        $telefono = $this->ur->findOneBy(['telefono' => $mensaje->telefono]);
        $tipo = $telefono ? TipoUsuario::CLIENTE : TipoUsuario::NO_CLIENTE;
        $usuario = $this->configRepo->findOneBy(['tipoLlamada' => $tipo]);
        $nuevoPrompt = $usuario->getPrompt();
        if ($conversacion) {
            if ($usuario->getTipoLlamada()->value === 'cliente') {

                if ($telefono) {
                    $nombreUsuario = $telefono->getUsername();
                    $rolUsuario = implode(', ', $telefono->getRoles());
                    $nuevoPrompt .= "
                        INFORMACIÓN DEL USUARIO QUE LLAMA:
                            -Nombre de usuario: $nombreUsuario
                            -Rol del usuario: $rolUsuario
                         ";
                }
            }
            $nuevoPrompt .= "HISTORIAL DE LA CONVERSACIÓN:
                        $conversacion

                        ÚLTIO MENSAJE DEL USUARIO:
                        $mensaje->textoUsuario
                    ";

        }
        $this->ia->setSysPrompt($nuevoPrompt);
        $promptFinal = $this->ia->generarRespuesta($mensaje->textoUsuario);
        $mensajeBot = new Mensaje();
        $mensajeBot->setTelefono($telefono?->getTelefono() ?? $mensaje->telefono);
        $mensajeBot->setTexto($promptFinal);
        $mensajeBot->setRol(TipoInteraccion::IA->value);
        $mensajeBot->setFecha(new \DateTimeImmutable());
        $mensajeBot->setCallSid($mensaje->callSid);

        $this->em->clear();
        $llamadaCheck = $this->mr->findOneBy(['callSid' => $mensaje->callSid, 'finalizado' => true]);
        if ($llamadaCheck) {
            return;
        }

        $this->em->persist($mensajeBot);
        $this->em->flush();
    }


}