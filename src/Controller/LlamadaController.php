<?php

namespace App\Controller;

use App\Entity\ConfiguracionLlamada;
use App\Entity\Mensaje;
use App\Entity\Usuario;
use App\Enum\TipoUsuario;
use App\Enum\TipoInteraccion;
use App\Form\ConfigCallType;
use App\Repository\ConfiguracionLlamadaRepository;
use App\Repository\MensajeRepository;
use App\Service\IAService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LlamadaController extends AbstractController
{

    #[Route('/webhook', methods: ['POST'])]
    public function webhook(Request $request, EntityManagerInterface $em, IAService $ia, MensajeRepository $mr, ConfiguracionLlamadaRepository $configCallRepo): Response
    {
        $path = $request->query->get('path');
        $telefono = $request->request->get('From');
        $textoUsuario = $request->request->get('SpeechResult');
        $digitoPulsado = $request->request->get('Digits');
        $conversacion = $mr->getUltimosMensajes($telefono);
        $xml = "";
        $usuario = $this->getTipoUsuario($mr, $telefono, $configCallRepo);
        $opciones = $usuario->getOpcionDesplegable();
        if ($digitoPulsado !== null) {
            return $this->tecladoNumerico($digitoPulsado, $opciones, $path);
        }

        $mensajeUsu = new Mensaje();
        $mensajeUsu->setTelefono($telefono);
        $mensajeUsu->setTexto($textoUsuario);
        $mensajeUsu->setFecha(new \DateTimeImmutable());
        $mensajeUsu->setRol('Usuario');
        $em->persist($mensajeUsu);
        $em->flush();

        $mensajeBot = new Mensaje();
        $mensajeBot->setTelefono($telefono);
        $mensajeBot->setRol('IA');
        $mensaje = '';
        if ($usuario->getTipoInteraccion()->value === 'ia') {
            $conversacion = $mr->getUltimosMensajes($telefono);
            $nuevoPrompt = $usuario->getPrompt();
            if ($conversacion) {
                if ($usuario->getTipoLlamada()->value === 'cliente') {
                    $datosUsuario = $em->getRepository(Usuario::class)->findOneBy(['telefono' => $telefono]);
                    $nombreUsuario = $datosUsuario->getUsername();
                    $rolUsuario = $datosUsuario->getRoles();
                    $nuevoPrompt .= "
                        INFORMACIÓN DEL USUARIO QUE LLAMA:
                            -Nombre de usuario: $nombreUsuario
                            -Rol del usuario: $rolUsuario
                         ";
                }
                $nuevoPrompt .= "HISTORIAL DE LA CONVERSACIÓN:
                        $conversacion

                        ÚLTIO MENSAJE DEL USUARIO:
                        $textoUsuario
                    ";

            }
            $ia->setSysPrompt($nuevoPrompt);
            $mensaje = $ia->generarRespuesta($nuevoPrompt);
        } else {
            $opciones = $usuario->getOpcionDesplegable();

            foreach ($opciones as $opcion) {
                if ($opcion['desplegable'] === 'mensaje_inicial' && !empty($opcion['mensajeInicial'])) {
                    $mensaje .= $opcion['mensajeInicial'] . " ";
                }

                if (!empty($opcion['tecla']) && !empty($opcion['label'])) {
                    $mensaje .= "Pulse " . $opcion['tecla'] . " para " . $opcion['label'] . ". ";
                }
            }
        }


        $mensajeBot->setTexto($mensaje);
        $mensajeBot->setFecha(new \DateTimeImmutable());
        $em->persist($mensajeBot);
        $em->flush();
        $mensajeLimpio = htmlspecialchars($mensajeBot->getTexto(), ENT_XML1, 'UTF-8');
        try {
            return new Response($this->generarXmlMensaje($mensajeLimpio), 200, ['Content-Type' => 'application/xml']);
        } catch (\Exception $e) {
            $errorInterno = "Lo siento, estamos saturados en estos momentos." . $e->getMessage();
            return new Response($this->generarXmlMensaje($errorInterno), 200, ['Content-Type' => 'application/xml']);
        }
    }

    #[Route('/webhook/configuracion', name: "app_webhook_inicio")]
    function configuracion(Request $request, ConfiguracionLlamadaRepository $configCallRepo, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $tipoUsuarioActual = $request->query->get('tipo', TipoUsuario::ANONIMO->value);
        $configLlamada = $configCallRepo->findOneBy(['tipoLlamada' => $tipoUsuarioActual]) ?? new ConfiguracionLlamada();

        if (!$configLlamada->getId()) {
            $tipoEnum = TipoUsuario::tryFrom($tipoUsuarioActual) ?? TipoUsuario::ANONIMO;
            $configLlamada->setTipoLlamada($tipoEnum);
        }

        $tipoInteraccionActual = $configLlamada->getTipoInteraccion()?->value ?? TipoInteraccion::IA->value;
        $desplegableActual = $configLlamada->getOpcionDesplegable() ?? [];
        $form = $this->createForm(ConfigCallType::class, $configLlamada);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $tipoInteraccion = $formData->getTipoInteraccion();
            if ($tipoInteraccion->value === 'ia') {
                $prompt = $formData->getPrompt();
                $configLlamada->setOpcionDesplegable([]);
                if ($prompt) {
                    $configLlamada->setPrompt($prompt);
                }
            } else if ($tipoInteraccion->value === 'teclado') {
                $configLlamada->setPrompt(null);
                $configLlamada->setOpcionDesplegable($formData->getOpcionDesplegable());
            }
            $em->persist($configLlamada);
            $em->flush();

            $this->addFlash('success', 'Configuración guardada con éxito');
            return $this->redirectToRoute('app_webhook_inicio');
        }


        return $this->render("llamadas/index.html.twig", [
            'configForm' => $form->createView(),
            'tipoUsuarioActual' => $tipoUsuarioActual,
            'tipoInteraccionActual' => $tipoInteraccionActual,
            'desplegableActual' => $desplegableActual,
        ]);
    }

    

    function getTipoUsuario($mr, $numeroTelefono, $configCallRepo)
    {
        $anonimo = ['anonymous', 'private', 'unavailable', 'restricted', '+2666'];
        $usuario = '';
        if (in_array($numeroTelefono, $anonimo)) {
            return $configCallRepo->findOneBy(['tipoLlamada' => TipoUsuario::ANONIMO]);
        }
        $telefonoEncontrado = $mr->findOneBy(['telefono' => $numeroTelefono]);
        if ($telefonoEncontrado) {
            return $configCallRepo->findOneBy(['tipoLlamada' => TipoUsuario::CLIENTE]);
        }
        if (!$telefonoEncontrado) {
            return $configCallRepo->findOneBy(['tipoLlamada' => TipoUsuario::NO_CLIENTE]);
        }
    }

    function tecladoNumerico($digito, $opciones, $path = '')
    {
        $mensaje = "Opción no válida. Por favor, pulse una opción válida.";
        $redireccion = false;
        if (!empty($path)) {
            $path = $path . ',';
        }
        foreach ($opciones as $opcion) {
            if (isset($opcion['tecla']) && $opcion['tecla'] == $digito) {
                if ($opcion['desplegable'] === 'mensaje_inicial') {
                    $mensaje = $opcion['mensajeInicial'] ?? $mensaje;
                    $path .= $opcion['tecla'];
                } else if ($opcion['desplegable'] === 'transferir') {
                    $mensaje = "Transfiriendo su llamada, por favor espere.";
                    $redireccion = true;
                } else if ($opcion['desplegable'] === 'mensajePersonalizado') {
                    $mensaje = $opcion['mensajePersonalizado'];
                    $path .= $opcion['tecla'];
                } else if ($opcion['desplegable'] === 'submenu') {
                    $path .= $opcion['tecla'];
                }
            }
        }
        if ($redireccion) {
            return new Response($this->generarXmlMensaje($mensaje, $opcion['numeroAgente']), 200, ['Content-Type' => 'application/xml']);
        }
        return new Response($this->generarXmlMensaje($mensaje), 200, ['Content-Type' => 'application/xml']);
    }

    function generarXmlMensaje($mensaje, ?string $numeroAgente = null, $path = '')
    {
        $xml = '';
        $mensajeLimpio = htmlspecialchars($mensaje, ENT_XML1, 'UTF-8');

        if ($numeroAgente) {
            $xml = "<?xml version='1.0' encoding='UTF-8'?>
        <Response>
            <Say language='es-ES' voice='female'>$mensajeLimpio</Say>
            <Dial>.$numeroAgente.</Dial>
        </Response>";
        } else {
            $xml = "<?xml version='1.0' encoding='UTF-8'?>
        <Response>
            <Gather input='speech dtmf' language='es-ES' timeout='5' action='/webhook?path=". $path ."' method='POST'>
                <Say language='es-ES' voice='female'>$mensajeLimpio</Say>
            </Gather>
        </Response>";
        }

        return $xml;
    }
}