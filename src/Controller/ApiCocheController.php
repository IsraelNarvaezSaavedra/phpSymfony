<?php

namespace App\Controller;

use App\Entity\Coche;
use App\Form\CocheType;
use App\Repository\CocheRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotNull;

#[Route('/api/coches')]
final class ApiCocheController extends AbstractController
{
    /*
        Formulario para generar el base64 de la imagen introducida 
        por el usuario para hacer pruebas con la api
    */
    #[Route('/chorizo', name: 'app_api_chorizo')]
    public function chorizo(Request $request)
    {
        $form = $this->createForm(CocheType::class);
        $form->remove('marca');
        $form->remove('modelo');
        $form->remove('precio');
        $form->add('fotoCoche', FileType::class, [
            'label' => 'Foto del Coche (JPG, PNG)',
            'mapped' => false,
            'required' => true,
            'constraints' => [
                new Image([
                    'maxSize' => '1024k',
                    'mimeTypes' => ['image/jpeg', 'image/png'],
                ]),
                new NotNull(['message' => 'Por favor suba una foto'])
            ],
        ]);


        $form->handleRequest($request);
        $chorizo = '';

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $fotoFile */
            $fotoCoche = $form->get('fotoCoche')->getData();
            if ($fotoCoche) {
                $binario = file_get_contents($fotoCoche->getPathname());
                $base64 = base64_encode($binario);
                if (
                    $fotoCoche->guessExtension() == 'jpeg' ||
                    $fotoCoche->guessExtension() == 'jpg'
                ) {
                    $mimo = 'image/jpeg';
                } else {
                    $mimo = 'image/png';
                }
            }

            $chorizo = 'data:' . $mimo . ';base64,' . $base64;
        }

        return $this->render("coches/chorizo.html.twig", [
            "generarb64" => $form,
            'chorizo' => $chorizo
        ]);
    }


    //Documentación de la API, con los endpoints disponibles para admin y usuario normal
    #[Route('/doc', name: 'app_api_guiacoches', methods: ['GET'])]
    public function documentacion(CocheRepository $cocheRepository): JsonResponse
    {
        $admin = $this->isGranted("ROLE_ADMIN_COCHE");
        if ($admin) {
            return $this->json([
                "endpoints" => [
                    "GET    /api/coches",
                    "GET    /api/coches/doc",
                    "GET    /api/coches/{id}",
                    "GET    /api/coches/marca/{marca}",
                    "GET    /api/coches/modelo/{modelo}",
                    "POST   /api/coches",
                    "PUT    /api/coches/edit/{id}",
                    "DELETE /api/coches/delete/{id}"
                ]
            ]);
        }
        return $this->json([
            "endpoints" => [
                "GET /api/coches",
                "GET /api/coches/doc",
                "GET /api/coches/{id}",
                "GET /api/coches/marca/{marca}",
                "GET /api/coches/modelo/{modelo}"
            ]
        ]);
    }


    //Endpoint que muestra todos los coches con todos sus datos
    #[Route('', name: 'app_api_coches', methods: ['GET'])]
    public function index(CocheRepository $cocheRepository): JsonResponse
    {
        return $this->json(
            $cocheRepository->findAll(),
            200,//Respuesta ok del server
            [],//Cabecera
            ["groups" => ["coche:read"]]//los grupos marcados como coche en la entidad
        );
    }


    //Endpoint que muestra un unico coche con todos sus datos, buscado por su id
    #[Route('/{id}', name: 'app_api_idcoche', methods: ['GET'])]
    public function encontrarPorId(Coche $c, CocheRepository $cocheRepository)
    {

        return $this->json(
            $cocheRepository->find($c->getId()),
            200,
            [],
            ["groups" => ["coche:read"]]
        );
    }


    //Endpoint que muestra todos los coches de una marca concreta con todos los datos de cada coche
    #[Route('/marca/{marca}', name: 'app_api_marcacoche', methods: ['GET'])]
    public function encontrarPorMarca($marca, CocheRepository $cocheRepository)
    {
        return $this->json(
            $cocheRepository->findByMarca($marca),
            200,
            [],
            ["groups" => ["coche:read"]]
        );
    }


    //Endpoint que muestra todos los coches de un modelo concreto con todos los datos de cada coche
    #[Route('/modelo/{modelo}', name: 'app_api_modelocoche', methods: ['GET'])]
    public function encontrarPorModelo($modelo, CocheRepository $cocheRepository)
    {
        $coches = $cocheRepository
            ->findByModelo($modelo)
            ->getQuery()
            ->getResult();

        return $this->json(
            $coches,
            200,
            [],
            ["groups" => ["coche:read"]]

        );
    }


    //Endpoint que permite crear un coche nuevo solo si eres admin de coches
    #[Route('', name: 'app_api_crearcoche', methods: ['POST'])]
    public function crearCoche(EntityManagerInterface $em, Request $request, CocheRepository $cr)
    {
        $this->denyAccessUnlessGranted("ROLE_ADMIN_COCHE");
        $data = json_decode($request->getContent(), true);
        $coche = new Coche;

        //Iniciales mayus
        $marca = $data["marca"] ?? '';
        $modelo = $data["modelo"] ?? '';
        $marca = mb_convert_case($marca, MB_CASE_TITLE, "UTF-8");
        $modelo = mb_convert_case($modelo, MB_CASE_TITLE, "UTF-8");

        $existeCoche = $cr->findCoche($marca, $modelo);

        if (empty($marca) || empty($modelo) || !is_numeric($data['precio'])) {
            return $this->json(['mensaje' => 'Los cuatro campos "marca, modelo, precio y fotoCoche" tienen que estar rellenos']);
        }

        if (!empty($existeCoche)) {
            return $this->json(['mensaje' => 'Ya existe este coche']);
        }

        $coche->setMarca($marca);
        $coche->setModelo($modelo);
        $coche->setPrecio($data["precio"]);
        $base64 = $data['fotoCoche'];
        if (str_contains($base64, ',')) {
            $base64 = explode(',', $base64)[1];
        }
        $decodificar = base64_decode($base64);
        $fichTemp = sys_get_temp_dir() . '/' . uniqid();
        file_put_contents($fichTemp, $decodificar);
        $file = new File($fichTemp);
        $nombreUnico = uniqid() . '.' . $file->guessExtension();
        $file->move(
            $this->getParameter('coches_directory'),
            $nombreUnico
        );
        $coche->setFotoCoche($nombreUnico);
        $em->persist($coche);
        $em->flush();

        return $this->json(
            $coche,
            201,
            [],
            ["groups" => ["coche:read"]]
        );
    }


    //Endpoint que permite editar un coche solo si eres admin de coches
    #[Route('/edit/{id}', name: 'app_api_actualizarcoche', methods: ['PUT', 'PATCH'])]
    public function editarCoche(EntityManagerInterface $em, Request $request, Coche $coche)
    {
        $this->denyAccessUnlessGranted("ROLE_ADMIN_COCHE");
        $data = json_decode($request->getContent(), true);
        if (!empty($data["marca"])) {
            $coche->setMarca(mb_convert_case($data["marca"], MB_CASE_TITLE, "UTF-8"));
        }

        if (!empty($data["modelo"])) {
            $coche->setModelo(mb_convert_case($data["modelo"], MB_CASE_TITLE, "UTF-8"));
        }

        if (isset($data["precio"])) {
            $coche->setPrecio($data["precio"]);
        }

        if (!empty($data["fotoCoche"])) {
            $base64 = $data['fotoCoche'];
            if (str_contains($base64, ',')) {
                $base64 = explode(',', $base64)[1];
            }
            $decodificado = base64_decode($base64);
            $fichTemp = sys_get_temp_dir() . '/' . uniqid();
            file_put_contents($fichTemp, $decodificado);
            $file = new File($fichTemp);
            $nombreUnico = uniqid() . '.' . $file->guessExtension();
            $file->move(
                $this->getParameter('coches_directory'),
                $nombreUnico
            );
            $coche->setFotoCoche($nombreUnico);
        }

        $em->flush();

        return $this->json(
            $coche,
            200,
            [],
            ["groups" => ["coche:read"]]
        );
    }


    //Endpoint que permite eliminar un coche por su id solo si eres admin de coches
    #[Route('/delete/{id}', name: 'app_api_eliminarcoche', methods: ['DELETE'])]
    public function eliminarCoche(EntityManagerInterface $em, Coche $coche)
    {
        $this->denyAccessUnlessGranted("ROLE_ADMIN_COCHE");
        $em->remove($coche);
        $em->flush();

        return $this->json(['mensaje' => 'Coche eliminado']);
    }
}
