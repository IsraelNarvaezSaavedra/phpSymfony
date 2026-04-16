<?php

namespace App\Controller;

use App\Entity\Usuario;
use App\Entity\Coche;
use App\Entity\Valoracion;
use App\Repository\ValoracionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class ValoracionController extends AbstractController
{
    //Sirve para valorar un coche tanto positiva como negativamente, solo si has iniciado sesion
    #[Route('/valoracion/{idCoche}/{valor}', name: 'app_valorar')]
    public function votarCoche($valor, Coche $idCoche, EntityManagerInterface $entityManager, ValoracionRepository $vr)
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        if (!$usuario) {
            return $this->redirectToRoute('app_login');
        }
        $existeVoto = $vr->findOneBy([
            'usuario' => $usuario,
            'coche' => $idCoche
        ]);
        
        if ($existeVoto && $valor == $existeVoto->getValor() ) {
            $existeVoto->setValor(0);
            $entityManager->persist($existeVoto);
        } elseif ($existeVoto && $valor != $existeVoto->getValor()) {
            $existeVoto->setValor($valor);
            $entityManager->persist($existeVoto);
        } else {
            $nuevaValoracion = new Valoracion();
            $nuevaValoracion->setValor($valor);
            $nuevaValoracion->setCoche($idCoche);
            $nuevaValoracion->setUsuario($usuario);
            $entityManager->persist($nuevaValoracion);
        }

        $entityManager->flush();
        return $this->redirectToRoute('app_index_coche');
    }
}