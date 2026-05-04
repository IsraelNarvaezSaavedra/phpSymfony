<?php
namespace App\Controller;

use App\Repository\UsuarioRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class IATwilio extends AbstractController
{
    #[Route('/webhook/nombre-usurio', methods: ['POST'])]
    public function getNombre(Request $request, UsuarioRepository $ur): JsonResponse
    {
        $telefono = trim($request->request->get('From') ?? '');
        $datosUsuario = $ur->findOneBy(['telefono' => $telefono]);

        if ($datosUsuario) {
            $nombreUsuario = $datosUsuario->getUsername();
            return new JsonResponse([$nombreUsuario, 200]);
        } else {
            return new JsonResponse(['error' => 'Usuario no encontrado'], 404);
        }
    }

    #[Route('/webhook/email', methods: ['POST'])]
    public function getEmail(Request $request, UsuarioRepository $ur): JsonResponse
    {
        $telefono = trim($request->request->get('From') ?? '');
        $datosUsuario = $ur->findOneBy(['telefono' => $telefono]);

        if ($datosUsuario) {
            $emailUsuario = $datosUsuario->getEmail();
            return new JsonResponse([$emailUsuario, 200]);
        } else {
            return new JsonResponse(['error' => 'Usuario no encontrado'], 404);
        }
    }
}