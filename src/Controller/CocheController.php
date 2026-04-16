<?php

namespace App\Controller;

use App\Entity\Coche;
use App\Form\CocheType;
use App\Repository\CocheRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/coches')]
class CocheController extends AbstractController
{
    //CRUD COCHES

    //Mostrar coches del crud
    #[Route(name: 'app_index_coche')]
    public function homepageCoche(
        CocheRepository $cocheRepository,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        //Buscar
        $nombre = $request->query->get('buscar');
        $queryBuilder = $cocheRepository->findByModelo($nombre);

        //Paginacion
        $coches = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            7
        );

        return $this->render("coches/index.html.twig", [
            "coches" => $coches
        ]);
    }

    //Crear coche
    #[Route('/new', name: 'app_create_coche')]
    public function crearCoche(
        CocheRepository $cocheRepository,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {

        $this->denyAccessUnlessGranted("ROLE_ADMIN_COCHE");
        $car = new Coche();
        $form = $this->createForm(CocheType::class, $car);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //Pasar la primera mayus
            $marca = $form->get("marca")->getData();
            $marca = mb_convert_case($marca, MB_CASE_TITLE, "UTF-8");
            $modelo = $form->get("modelo")->getData();
            $modelo = mb_convert_case($modelo, MB_CASE_TITLE, "UTF-8");

            $existeCoche = $cocheRepository->findCoche($marca, $modelo);

            if (!empty($existeCoche)) {
                $this->addFlash('error', 'Este coche ya existe, prueba con otro');
                return $this->render('coches/gestion/new.html.twig', [
                    'createFormCoche' => $form
                ]);
            }
            $fotoCoche = $form->get('fotoCoche')->getData();
            if ($fotoCoche) {
                $fotoUnica = uniqid().'.'.$fotoCoche->guessExtension();
                $fotoCoche->move(
                    $this->getParameter('coches_directory'), $fotoUnica
                );
                $car->setFotoCoche($fotoUnica);
            }

            $car->setMarca($marca);
            $car->setModelo($modelo);
            $entityManager->persist($car);
            $entityManager->flush();
            $this->addFlash('success', 'Se ha creado el coche con exito');
            return $this->redirectToRoute('app_index_coche');
        }
        return $this->render("coches/gestion/new.html.twig", [
            "createFormCoche" => $form
        ]);
    }

    //Editar coche
    #[Route("/edit/{id}", name: "app_edit_coche")]
    public function edit(CocheRepository $cocheRepository, Request $request, EntityManagerInterface $entityManager, $id): Response
    {
        $this->denyAccessUnlessGranted("ROLE_ADMIN_COCHE");
        $car = $cocheRepository->find($id);
        $form = $this->createForm(CocheType::class, $car);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            //Pasar la primera mayus
            $marca = $form->get("marca")->getData();
            $marca = mb_convert_case($marca, MB_CASE_TITLE, "UTF-8");
            $modelo = $form->get("modelo")->getData();
            $modelo = mb_convert_case($modelo, MB_CASE_TITLE, "UTF-8");

            $fotoCoche = $form->get('fotoCoche')->getData();

            if ($fotoCoche) {
                $fotoUnica = uniqid().'.'.$fotoCoche->guessExtension();
                $fotoCoche->move(
                    $this->getParameter('coches_directory'), $fotoUnica
                );
                $car->setFotoCoche($fotoUnica);
            }

            $car->setMarca($marca);
            $car->setModelo($modelo);
            $entityManager->persist($car);
            $entityManager->flush();
            
            $this->addFlash('success', 'Se ha editado el coche con exito');
            return $this->redirectToRoute('app_index_coche');
        }

        return $this->render("coches/gestion/edit.html.twig", [
            "editFormCoche" => $form,
            'coche'=> $car
        ]);
    }

    //Borrar coche
    #[Route("/delete/{id}", name: "app_delete_coche")]
    public function delete(CocheRepository $cocheRepository, EntityManagerInterface $entityManager, $id)
    {
        $this->denyAccessUnlessGranted("ROLE_ADMIN_COCHE");
        $car = $cocheRepository->find($id);
        if (!$car) {
            throw $this->createNotFoundException("Coche no encontrado");
        }
        $entityManager->remove($car);
        $entityManager->flush();
        $this->addFlash('success', 'Se ha eliminado el coche con exito');
        return $this->redirectToRoute('app_index_coche');
    }

}