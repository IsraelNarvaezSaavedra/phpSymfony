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
    public function webhook(Request $request, EntityManagerInterface $em, IAService $ia, MensajeRepository $mr)
    {

        $telefono = $request->request->get('From');
        $textoUsuario = $request->request->get('SpeechResult');
        $digitoPulsado = $request->request->get('Digits');

        $conversacion = $mr->getUltimosMensajes($telefono);
        $xml = "";
        $usuario = $em->getRepository(Usuario::class)->findOneBy(['telefono' => $telefono]);

        if ($digitoPulsado !== null) {
            return $this->tecladoNumerico($digitoPulsado, $usuario);
        }

        if (!$textoUsuario && !$usuario) {
            if (!$conversacion) {
                $primerMensaje = "Bienvenido a VivaGym. Soy su asistente virtual, en que puedo ayudarle";
            } else {
                $primerMensaje = "Perdona, no te he oído. ¿Me lo repites?";
            }
            $xml = "<?xml version='1.0' encoding='UTF-8'?>
            <Response>
                <Say language='es-ES'>$primerMensaje</Say>
                <Gather input='speech' language='es-ES' timeout='5' action='/webhook' method='POST' />
            </Response>";

            return new Response($xml, 200, ['Content-Type' => 'application/xml']);
        } else if (!$textoUsuario && $usuario) {
            if (!$conversacion) {
                $primerMensaje = [
                    "Buenas " . $usuario->getUsername() . ". Que necesita?",
                    "Pulse 1 para solicitar la informacion de su perfil.",
                    "Pulse 2 para solicitar informacion sobre nuestros servicios.",
                    "Pulse 3 para saber todas las clases grupales que ofrecemos.",
                    "Pulse 4 para hablar con un agente humano."
                ];
                $primerMensaje = implode(" ", $primerMensaje);
            } else {
                $primerMensaje = "Perdona, no te he oído. ¿Me lo repites?";
            }
            $xml = "<?xml version='1.0' encoding='UTF-8'?>
            <Response>
                <Gather input='speech dtmf' numDigits='1' language='es-ES' timeout='5' action='/webhook' method='POST' >
                <Say language='es-ES'>$primerMensaje</Say>
                </Gather>
            </Response>";

            return new Response($xml, 200, ['Content-Type' => 'application/xml']);
        }

        //Se almacena el mensaje de la persona en la base de datos
        $persona = new Mensaje();
        $persona->setTelefono($telefono);
        $persona->setTexto($textoUsuario);
        $persona->setRol('Usuario');
        $persona->setFecha(new \DateTimeImmutable());
        $em->persist($persona);
        $em->flush();

        $mensajeIA = new Mensaje();
        $mensajeIA->setTelefono($telefono);
        $mensajeIA->setRol('IA');
        $promptGeneral = "

                HISTORIAL DE LA CONVERSACIÓN (Léelo para no repetirte):
                $conversacion

                MENSAJE ACTUAL DEL USUARIO:
                $textoUsuario

                INSTRUCCIÓN: 
                    -Responde de forma natural y breve al mensaje actual. 
                    -Si el usuario se ha presentado, usa su nombre. 
                    -Si el usuario dice algo que ya respondió antes, avanza en la conversación.
        ";

        $estado = $mr->getEstado($telefono);
        $nombre = ($usuario) ? $usuario->getUsername() : null;
        $ultimoMensajeIA = $mr->findOneBy(['telefono' => $telefono, 'rol' => 'IA'], ['id' => 'DESC']);

        if (preg_match('/agente|soporte|humano|persona|hablar con alguien/i', $textoUsuario)) {
            $persona->setEstado('agente');
            $estado = $persona->getEstado();
        }
        if ($estado === 'inicio' && preg_match('/mi perfil|mi cuenta/i', $textoUsuario)) {
            $persona->setEstado('miPerfil');
            $estado = $persona->getEstado();
        }
        if ($estado === 'inicio' && preg_match('/registro|crear cuenta|registrarse|registrarme/i', $textoUsuario)) {
            $mensaje = "Lo sentimos, no puedes registrarte a través de esta vía. Por favor, visita nuestra página web para crear una cuenta. ¿Puedo ayudarte en algo más?";
            $mensajeIA->setTexto($mensaje);
            $em->persist($mensajeIA);
            $em->flush();
        }
        if ($estado === 'inicio' && preg_match('/información|informacion|info|informarme/i', $textoUsuario)) {
            $persona->setEstado('informacion');
            $estado = $persona->getEstado();
        }
        if ($ultimoMensajeIA && preg_match('/¿puedo ayudarte en algo más?/i', $ultimoMensajeIA->getTexto())) {
            $persona->setEstado('inicio');
            $estado = $persona->getEstado();
        }

        if ($usuario) {

            $rol = $usuario->getRoles()[0] ?? 'Usuario';
            $promptGeneral .= "

                Información del usuario:
                    - Nombre: $nombre
                    - Rol: $rol
                
                ";

            $promptGeneral = $this->estadoInteraccion($estado, $promptGeneral);
            $mensaje = $ia->generarRespuesta($promptGeneral);
        } else {
            $promptGeneral .= "

                Información del usuario:
                    -No disponemos de su informacion ya que no se ha registrado previamente.
                
                ";
            $promptGeneral = $this->estadoInteraccion($estado, $promptGeneral);
            $mensaje = $ia->generarRespuesta($promptGeneral);
        }

        $mensajeIA->setTexto($mensaje);
        $mensajeIA->setFecha(new \DateTimeImmutable());
        $em->persist($mensajeIA);
        $em->flush();
        $mensajeLimpio = htmlspecialchars($mensaje, ENT_XML1, 'UTF-8');
        try {
            $xml = "<?xml version='1.0' encoding='UTF-8'?>
            <Response>
                <Say language='es-ES' voice='female'>$mensajeLimpio</Say>
                <Gather input='speech' language='es-ES' timeout='5' action='/webhook' method='POST' />
            </Response>
            
            ";

            return new Response($xml, 200, ['Content-Type' => 'application/xml']);
        } catch (\Exception $e) {
            $errorInterno = "<?xml version='1.0' encoding='UTF-8'?>
            <Response>
                <Say language='es-ES'>Lo siento, estamos saturados en estos momentos." . $e->getMessage() . "</Say>
            </Response>";
            return new Response($errorInterno, 200, ['Content-Type' => 'application/xml']);
        }

    }

    /* #[Route('/webhook/configuracion', name: "app_webhook_inicio")]
     function configuracion(Request $request, ConfiguracionLlamadaRepository $configCallRepo, EntityManagerInterface $em, IAService $ia): Response
     {
         $this->denyAccessUnlessGranted('ROLE_ADMIN');
         $tipoUsuarioActual = $request->request->all('config_call')['tipoLlamada']
            ?? $request->query->get('tipo')
            ?? TipoUsuario::ANONIMO->value;
         $config = $configCallRepo->findOneBy(['tipoLlamada' => $tipoUsuarioActual]) ?? new ConfiguracionLlamada();
         $form = $this->createForm(ConfigCallType::class, $config);

         $form->handleRequest($request);

         if ($form->isSubmitted() && $form->isValid()) {
             $nuevoTipo = $config->getTipoLlamada();
             if (!$config->getId()) {
                 $config = new ConfiguracionLlamada();
             }
             $config->setTipoLlamada($nuevoTipo);

             $config->setTipoInteraccion($config->getTipoInteraccion());

             if ($config->getTipoInteraccion()->value === 'ia') {
                 $prompt = $config->getPrompt();
                 $config->setPrompt($prompt);
                 $config->setOpcionDesplegable([]);
                 if ($config->getPrompt()) {
                     $ia->getSysPrompt($config->getPrompt());
                 }
             } else {
                 $opciones = $config->getOpcionDesplegable();
                 $config->setOpcionDesplegable($opciones);
                 $config->setPrompt(null);
             }

             $em->persist($config);
             $em->flush();

             $this->addFlash('success', 'Configuración guardada con éxito');
             return $this->redirectToRoute('app_webhook_inicio');
         }

         return $this->render("llamadas/index.html.twig", [
             'configForm' => $form->createView()
         ]);
     }*/

    #[Route('/webhook/configuracion', name: "app_webhook_inicio")]
    function configuracion(Request $request, ConfiguracionLlamadaRepository $configCallRepo, EntityManagerInterface $em, IAService $ia): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // 1. DETERMINAR QUÉ ESTAMOS EDITANDO
        // Miramos si viene por la URL (GET) o por el formulario (POST)
        // Si no viene nada, por defecto editamos ANONIMO
        $tipoUsuarioActual = $request->request->all('config_call')['tipoLlamada']
            ?? $request->query->get('tipo')
            ?? TipoUsuario::ANONIMO->value;

        // Buscamos la entidad real en la base de datos
        $config = $configCallRepo->findOneBy(['tipoLlamada' => $tipoUsuarioActual]) ?? new ConfiguracionLlamada();

        // Si es nueva, le ponemos el tipo para que el formulario lo sepa
        if (!$config->getId()) {
            $tipoEnum = TipoUsuario::tryFrom($tipoUsuarioActual) ?? TipoUsuario::ANONIMO;
            $config->setTipoLlamada($tipoEnum);
        }

        $form = $this->createForm(ConfigCallType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Al usar $config directamente, si ya existía en la BD, Doctrine hará un UPDATE sobre su ID real.
            // Si no existe, al ser un objeto 'new', hará un INSERT. No habrá pisotones.

            $tipoInteraccion = $config->getTipoInteraccion();

            if ($tipoInteraccion->value === 'ia') {
                $config->setOpcionDesplegable([]);
                if ($config->getPrompt()) {
                    $ia->getSysPrompt($config->getPrompt());
                }
            } else {
                $config->setPrompt(null);
            }

            $em->persist($config);
            $em->flush();

            $this->addFlash('success', 'Configuración guardada para ' . $tipoUsuarioActual);

            // Redirigimos pasando el tipo para que el formulario se vuelva a cargar con los datos guardados
            return $this->redirectToRoute('app_webhook_inicio', ['tipo' => $tipoUsuarioActual]);
        }

        return $this->render("llamadas/index.html.twig", [
            'configForm' => $form->createView(),
            'tipoActual' => $tipoUsuarioActual
        ]);
    }

    function estadoInteraccion($estado, $promptGeneral)
    {
        switch ($estado) {
            case 'agente':
                $this->tecladoNumerico('4', null);
                break;
            case 'informacion':
                $promptGeneral .= "
                        El usuario ha solicitado información adicional. Proporciona información relevante sobre nuestros servicios de forma clara y concisa, en caso de que creas que has terminado con este tema dile, '¿puedo ayudarte en algo más?'.
                    ";
                break;

            case 'miPerfil':
                $promptGeneral .= "
                        El usuario ha solicitado informacion sobre su perfil. Responde de forma amable y cercana, dando información relevante sobre su perfil.
                    ";
                break;

            default:
                $promptGeneral .= "
                        Responde al mensaje del usuario de forma amable y cercana, ofreciendo ayuda o información según sea necesario.
                    ";
        }
        return $promptGeneral;
    }

    function tecladoNumerico($digitoPulsado, $usuario)
    {
        $mensaje = "";
        $colgar = false;
        switch ($digitoPulsado) {
            case '1':
                $mensaje = "Has pulsado el 1. El nombre de usuario asociado a este número es " . $usuario->getUsername() . " y su rol es " . ($usuario->getRoles()[0] ?? 'Usuario');
                break;
            case '2':
                $mensaje = "Has pulsado el 2. Nosotros ofrecemos planes personalizados de entrenamiento y nutrición, gimnasios en España y Portugal, entrenadores personales y clases grupales.";
                break;
            case '3':
                $mensaje = "Has pulsado el 3. Ofrecemos las siguientes clases grupales: yoga, pilates, spinning, zumba, crossfit, body pump, etc.";
                break;
            case '4':
                $mensaje = "Has pulsado el 4. Un agente humano se pondrá en contacto contigo lo antes posible.";
                $colgar = true;
                break;
            default:
                $mensaje = "Opción no válida. Por favor, pulsa un número del 1 al 4.";
                break;
        }
        $xml = "<?xml version='1.0' encoding='UTF-8'?>
    <Response>
        <Say language='es-ES'>$mensaje</Say>
        <Pause length='1'/>
        " . ($colgar ? "<Hangup />"/*"<Dial>+1234567890</Dial>"*/ : "<Say language='es-ES'>¿Desea algo más?</Say><Gather input='speech dtmf' numDigits='1' language='es-ES' timeout='5' action='/webhook' method='POST' />") . "
    </Response>";

        return new Response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}