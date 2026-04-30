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
use App\Repository\UsuarioRepository;
use App\Service\IAService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LlamadaController extends AbstractController
{

    #[Route('/webhook', methods: ['POST'])]
    public function webhook(Request $request, EntityManagerInterface $em, IAService $ia, MensajeRepository $mr, UsuarioRepository $ur, ConfiguracionLlamadaRepository $configCallRepo): Response
    {
        $telefono = trim($request->request->get('From') ?? '');
        $textoUsuario = trim($request->request->get('SpeechResult') ?? '(Sin respuesta)');
        $digitoPulsado = trim($request->request->get('Digits') ?? '');
        $path = trim($request->query->get('path') ?? '');
        $final = $request->query->get('final') === 'true';

        if (!$telefono) {
            $telefono = TipoUsuario::ANONIMO->value;
        }

        $usuario = $this->getTipoUsuario($ur, $telefono, $configCallRepo);
        if (!$usuario) {
            return new Response($this->generarXmlMensaje('Configuración no disponible'), 200, ['Content-Type' => 'application/xml']);
        }

        $opciones = $usuario->getOpcionDesplegable();
        $conversacion = $mr->getUltimosMensajes($telefono);

        if ($final) {
            return $this->opcionesFinales($digitoPulsado);
        }

        if ($digitoPulsado) {
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
            $nuevoPrompt = $usuario->getPrompt();
            if ($conversacion) {
                if ($usuario->getTipoLlamada()->value === 'cliente') {
                    $datosUsuario = $em->getRepository(Usuario::class)->findOneBy(['telefono' => $telefono]);
                    if ($datosUsuario) {
                        $nombreUsuario = $datosUsuario->getUsername();
                        $rolUsuario = $datosUsuario->getRoles();
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
                        $textoUsuario
                    ";

            }
            $ia->setSysPrompt($nuevoPrompt);
            $mensaje = $ia->generarRespuesta($nuevoPrompt);
        } else {
            $pathActual = $this->getPathActual($opciones, $path);


            foreach ($pathActual as $opcion) {
                if (!empty($opcion['mensajeInicial'])) {
                    $mensaje .= $opcion['mensajeInicial'] . ". \n";
                }
            }

            foreach ($pathActual as $opcion) {
                if (!empty($opcion['tecla']) && !empty($opcion['label'])) {
                    $mensaje .= "Pulse " . $opcion['tecla'] . " para " . $opcion['label'] . ". \n";
                }
            }
        }


        $mensajeBot->setTexto($mensaje);
        $mensajeBot->setFecha(new \DateTimeImmutable());
        $em->persist($mensajeBot);
        $em->flush();
        try {
            return new Response($this->generarXmlMensaje($mensajeBot->getTexto(), null, $path), 200, ['Content-Type' => 'application/xml']);
        } catch (\Exception $e) {
            return new Response($this->generarXmlMensaje('Lo siento, estamos saturados en estos momentos.', null, $path), 200, ['Content-Type' => 'application/xml']);
        }
    }

    #[Route('/webhook/configuracion', name: "app_webhook_inicio")]
    public function configuracion(Request $request, ConfiguracionLlamadaRepository $configCallRepo, EntityManagerInterface $em): Response
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
            return $this->redirectToRoute('app_webhook_inicio', ['tipo' => $tipoUsuarioActual]);
        }

        return $this->render("llamadas/index.html.twig", [
            'configForm' => $form->createView(),
            'tipoUsuarioActual' => $tipoUsuarioActual,
            'tipoInteraccionActual' => $tipoInteraccionActual,
            'desplegableActual' => $desplegableActual,
        ]);
    }

    #[Route('/ia/generar-prompt', name: 'app_ia_generar_prompt', methods: ['POST'])]
    public function generarPromptIA(IAService $ia): JsonResponse
    {
        $sysPrompt = "
            ACTÚA COMO UN INGENIERO DE IA ESPECIALIZADO EN CONFIGURACIÓN DE LLAMADAS.
TU ÚNICA TAREA ES GENERAR UN 'SYSTEM PROMPT' TÉCNICO.

PROHIBICIONES ABSOLUTAS:
- NO escribas como si fueras la empresa.
- NO uses frases de bienvenida hacia el usuario del programa.
- NO uses negritas (**), almohadillas (#) ni asteriscos (*).
- NO uses la primera persona (nosotros).

INSTRUCCIONES DE REDACCIÓN:
- Usa siempre el imperativo: 'Eres el asistente...', 'Debes responder...', 'No permitas...'.
- Crea un manual de comportamiento exhaustivo.
- Divide el texto en secciones claras usando solo saltos de línea y mayúsculas.
- Cuando sea necesario, incluye placeholders entre corchetes [ASÍ] para indicar dónde se insertará información dinámica.
- Asegúrate de que el prompt sea lo más detallado posible, cubriendo todas las situaciones imaginables.
- Cuando generes el prompt, deja en claro que no se invente informacion y que use solo la información proporcionada en el
 prompt o en los mensajes anteriores además de que en caso de que no pueda resolver algo le sugiera al usuario que se ponga 
 en contacto con un agente.


ESTRUCTURA DEL PROMPT A GENERAR:
1. IDENTIDAD Y ROL: Definición del asistente virtual.
2. COMPORTAMIENTO Y RESPUESTAS: Cómo debe tratar al usuario (con amabilidad, formalidad, etc.).
3. LÓGICA DE ATENCIÓN: Cómo debe razonar la IA.
4. PROTOCOLO DE CRISIS Y TRANSFERENCIA: Cuándo pasar la llamada a un humano.
5. RESTRICCIONES: Qué NO puede decir la IA.
        ";
        $ia->setSysPrompt($sysPrompt);
        $prompt = '
        Escribe un manual de instrucciones de 500 palabras para un bot asistente virtual de una empresa 
        de [SECTOR]. Redacta todas las instrucciones en segunda persona del singular (ERES, DEBES, HAZ). 
        Incluye una lógica compleja de toma de decisiones y placeholders [CORCHETES].
        ';
        $textoGenerado = $ia->generarRespuesta($prompt);

        return new JsonResponse(['texto' => $textoGenerado]);
    }


    private function getTipoUsuario(UsuarioRepository $ur, string $numeroTelefono, ConfiguracionLlamadaRepository $configCallRepo)
    {
        $anonimo = ['anonymous', 'private', 'unavailable', 'restricted', '+2666', 'user', 'unknown', 'zoiper'];

        if (in_array($numeroTelefono, $anonimo)) {
            return $configCallRepo->findOneBy(['tipoLlamada' => TipoUsuario::ANONIMO]);
        }
        $telefonoEncontrado = $ur->findOneBy(['telefono' => $numeroTelefono]);
        if ($telefonoEncontrado) {
            return $configCallRepo->findOneBy(['tipoLlamada' => TipoUsuario::CLIENTE]);
        }
        if (!$telefonoEncontrado) {
            return $configCallRepo->findOneBy(['tipoLlamada' => TipoUsuario::NO_CLIENTE]);
        }
    }

    private function getPathActual(array $opcionesPath, string $path): array
    {
        // Validar entrada
        if (!is_array($opcionesPath) || empty($opcionesPath)) {
            return [];
        }

        if (!$path || $path === '') {
            return $opcionesPath;
        }

        $partesPath = explode(',', $path);
        $opcionesActuales = $opcionesPath;

        foreach ($partesPath as $i) {
            $idx = (int) $i;

            if (isset($opcionesActuales[$idx])) {
                if (isset($opcionesActuales[$idx]['submenu']) && !empty($opcionesActuales[$idx]['submenu']) && is_array($opcionesActuales[$idx]['submenu'])) {
                    $opcionesActuales = $opcionesActuales[$idx]['submenu'];
                } else {
                    return [$opcionesActuales[$idx]];
                }
            } else {
                return $opcionesActuales;
            }
        }
        return $opcionesActuales;
    }

    private function opcionFinal(array $opcion): bool
    {
        return !isset($opcion['submenu']) || empty($opcion['submenu']) ?? true;
    }

    private function tecladoNumerico(int $digito, array $opciones, string $path = ''): Response
    {
        $mensaje = "Opción no válida. Por favor, pulse una opción válida.";

        if (empty($opciones)) {
            return new Response($this->generarXmlMensaje("Lo siento, el menú no está configurado correctamente.", null, $path), 200, ['Content-Type' => 'application/xml']);
        }

        $pathActual = $this->getPathActual($opciones, $path);

        if (!empty($pathActual) && is_array($pathActual)) {
            foreach ($pathActual as $key => $opcion) {
                if (is_array($opcion) && isset($opcion['tecla']) && (string) $opcion['tecla'] == (string) $digito) {
                    $final = $this->opcionFinal($opciones);
                    if ($path == '') {
                        $nuevoPath = (string) $key;
                    } else {
                        $nuevoPath = $path . ',' . $key;
                    }

                    if ($opcion['desplegable'] === 'mensaje_inicial') {
                        return new Response($this->generarXmlMensaje($opcion['mensajeInicial'], null, $path), 200, ['Content-Type' => 'application/xml']);
                    }
                    if ($opcion['desplegable'] === 'transferir') {
                        $mensaje = "Transfiriendo su llamada, por favor espere.";
                        return new Response($this->generarXmlMensaje($mensaje, $opcion['numeroAgente']), 200, ['Content-Type' => 'application/xml']);
                    }
                    if ($opcion['desplegable'] === 'mensaje') {
                        return new Response($this->generarXmlMensaje($opcion['mensajePersonalizado'], null, $path, $final), 200, ['Content-Type' => 'application/xml']);
                    }
                    if ($opcion['desplegable'] === 'submenu') {
                        $textoSub = $opcion['mensajeInicial'] ?? '';

                        if (!empty($opcion['submenu']) && is_array($opcion['submenu'])) {
                            foreach ($opcion['submenu'] as $sub) {
                                if (isset($sub['tecla']) && isset($sub['label'])) {
                                    $textoSub .= "Pulse " . $sub['tecla'] . " para " . $sub['label'] . ". ";
                                }
                            }
                        }
                        return new Response($this->generarXmlMensaje($textoSub, null, $nuevoPath), 200, ['Content-Type' => 'application/xml']);
                    }
                }
            }
        }

        return new Response($this->generarXmlMensaje($mensaje, null, $path), 200, ['Content-Type' => 'application/xml']);
    }

    private function generarXmlMensaje(string $mensaje, ?string $numeroAgente = null, string $path = '', bool $final = false): string
    {
        $xml = '';
        $request = Request::createFromGlobals();
        $intento = (int) $request->query->get('retry', 0);
        $mensajeLimpio = htmlspecialchars($mensaje, ENT_XML1, 'UTF-8');
        $numeroAgenteLimpio = htmlspecialchars($numeroAgente ?? '', ENT_XML1, 'UTF-8');
        $actualizarFinal = $final ? 'true' : 'false';
        $p = urlencode($path ?? '');
        if ($intento >= 2) {
            return "<?xml version='1.0' encoding='UTF-8'?>
            <Response>
                <Say language='es-ES' voice='female'>Vaya, parece que no hay nadie, Gracias por su llamada.</Say>
                <Hangup/>
            </Response>";
        }
        if ($numeroAgente) {
            $xml = "<?xml version='1.0' encoding='UTF-8'?>
        <Response>
            <Say language='es-ES' voice='female'>$mensajeLimpio</Say>
            <Dial>$numeroAgenteLimpio</Dial>
        </Response>";
        } else {
            $siguienteIntento = $intento + 1;
            $xml = "<?xml version='1.0' encoding='UTF-8'?>
        <Response>
            <Gather input='speech dtmf' language='es-ES' timeout='5' action='/webhook?path=$p&amp;final=$actualizarFinal' method='POST'>
                <Say language='es-ES' voice='female'>$mensajeLimpio</Say>
            </Gather>
            <Redirect>/webhook?path=$p&amp;retry=$siguienteIntento&amp;final=$actualizarFinal</Redirect>
        </Response>";
        }

        return $xml;
    }

    private function opcionesFinales(string $digitoPulsado): Response
    {
        if ($digitoPulsado === '') {
            $xml = "<?xml version='1.0' encoding='UTF-8'?>
        <Response>
            <Gather input='dtmf' timeout='5' action='/webhook?final=true' method='POST'>
                <Say language='es-ES' voice='female'>Pulse 1 para volver al inicio, 2 para hablar con un agente, o 3 para finalizar.</Say>
            </Gather>
            <Hangup/>
        </Response>";
            return new Response($xml, 200, ['Content-Type' => 'application/xml']);
        }

        switch ($digitoPulsado) {
            case '1':
                $xml = "<?xml version='1.0' encoding='UTF-8'?>
                <Response>
                    <Say language='es-ES' voice='female'>Volviendo al inicio del menú.</Say>
                    <Redirect>/webhook?final=false</Redirect>
                </Response>";
                return new Response($xml, 200, ['Content-Type' => 'application/xml']);
                break;
            case '2':
                return new Response($this->generarXmlMensaje("Transfiriendo su llamada, por favor espere.", "123456789"), 200, ['Content-Type' => 'application/xml']);
                break;
            case '3':
            default:
                $xml = "<?xml version='1.0' encoding='UTF-8'?>
                <Response>
                    <Say language='es-ES' voice='female'>Gracias por su llamada, que tenga un buen día.</Say>
                    <Hangup/>
                </Response>";
                return new Response($xml, 200, ['Content-Type' => 'application/xml']);

        }
    }
}