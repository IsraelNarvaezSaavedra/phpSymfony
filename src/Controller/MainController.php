<?php

namespace App\Controller;

use App\Entity\Usuario;
use App\Form\UsuarioType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UsuarioRepository;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Knp\Component\Pager\PaginatorInterface;

class MainController extends AbstractController
{
    //PAGINA INICIO
    #[Route('/', name: 'app_index')]
    public function choose(): Response
    {
        return $this->render("choose.html.twig");
    }

    //PERFIL

    //Ver perfil propio
    #[Route('/perfil', name: 'app_perfil')]
    public function perfilUsuario()
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        return $this->render("perfil/perfil.html.twig");
    }

    //Editar perfil propio
    #[Route('/editarPerfil', name: 'app_editar_perfil')]
    public function editarPerfilUsuario(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager)
    {
        /** @var Usuario $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(UsuarioType::class, $user);
        $form->remove('rol');
        $form->handleRequest($request);        

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                // encode the plain password
                $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            }

            $nombreFoto = $form->get('fotoPerfil')->getData();
            if ($nombreFoto) {
                $nombreUnico = uniqid() . '.' . $nombreFoto->guessExtension();
                $nombreFoto->move(
                    $this->getParameter('perfiles_directory'),
                    $nombreUnico
                );
                $user->setFotoPerfil($nombreUnico);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Se ha editado el perfil con exito');
            return $this->redirectToRoute('app_perfil');
        }

        return $this->render("perfil/editarPerfil.html.twig", [
            "editForm" => $form
        ]);
    }

    //CRUD USUARIOS

    //Mostrar usuarios del crud
    #[Route('/usuarios', name: 'app_index_usuario')]
    public function homepage(
        UsuarioRepository $usuarioRepository,
        Request $request,
        PaginatorInterface $paginator
    ): Response {

        //Buscar
        $nombre = $request->query->get('buscar');
        $queryBuilder = $usuarioRepository->findByUsername($nombre);

        //Paginacion
        $usuarios = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            7
        );


        return $this->render("index.html.twig", [
            "usuarios" => $usuarios
        ]);
    }

    //Editar usuario
    #[Route("/edit/{id}", name: "app_edit")]
    public function edit(UsuarioRepository $usuarioRepository, Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, $id): Response
    {
        $this->denyAccessUnlessGranted("ROLE_ADMIN");
        $user = $usuarioRepository->find($id);
        $form = $this->createForm(UsuarioType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                // encode the plain password
                $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            }

            $nombreFoto = $form->get('fotoPerfil')->getData();
            if ($nombreFoto) {
                $nombreUnico = uniqid() . '.' . $nombreFoto->guessExtension();
                $nombreFoto->move(
                    $this->getParameter('perfiles_directory'),
                    $nombreUnico
                );
                $user->setFotoPerfil($nombreUnico);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email
            $this->addFlash('success', 'Se ha editado el usuario con exito');
            return $this->redirectToRoute('app_index_usuario');
        }

        return $this->render("gestion/edit.html.twig", [
            "editForm" => $form,
            'userEdit' => $user
        ]);
    }

    //Crear nuevo usuario
    #[Route("/new", name: "app_create")]
    public function new(UsuarioRepository $usuarioRepository, Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted("ROLE_ADMIN");
        $user = new Usuario();
        $form = $this->createForm(UsuarioType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $correoUsado = $usuarioRepository->findOneBy(["email" => $form->get("email")->getData()]);
            $nombreUsado = $usuarioRepository->findOneBy(["username" => $form->get("username")->getData()]);

            if ($nombreUsado) {
                $this->addFlash('error', 'Ya existe un usuario con ese nombre de usuario.');
                return $this->render('gestion/new.html.twig', [
                    'createForm' => $form,
                ]);
            }
            if ($correoUsado) {
                $this->addFlash('error', 'Ya existe un usuario con ese correo electrónico.');
                return $this->render('gestion/new.html.twig', [
                    'createForm' => $form,
                ]);
            }
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email
            $this->addFlash('success', 'Se ha creado el usuario con exito');
            return $this->redirectToRoute('app_index_usuario');
        }

        return $this->render("gestion/new.html.twig", [
            "createForm" => $form
        ]);
    }

    //Borrar usuario
    #[Route("/delete/{id}", name: "app_delete")]
    public function delete(UsuarioRepository $usuarioRepository, EntityManagerInterface $entityManager, $id)
    {
        $this->denyAccessUnlessGranted("ROLE_ADMIN");
        $user = $usuarioRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException("Usuario no encontrado");
        }
        $entityManager->remove($user);
        $entityManager->flush();
        $this->addFlash('success', 'Se ha borrado el usuario con exito');
        return $this->redirectToRoute('app_index_usuario');
    }
}