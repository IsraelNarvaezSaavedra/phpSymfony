<?php

namespace App\Controller;

use App\Entity\Usuario;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UsuarioRepository;

class RegistrationController extends AbstractController
{
    public function __construct()
    {
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, UsuarioRepository $usuarioRepository): Response
    {
        $user = $this->getUser();
        if ($user) {
            return $this->redirectToRoute('app_index');
        }
        $user = new Usuario();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $correoUsado = $usuarioRepository->findOneBy(["email" => $form->get("email")->getData()]);
            $nombreUsado = $usuarioRepository->findOneBy(["username" => $form->get("username")->getData()]);
            $telefonoUsado = $usuarioRepository->findOneBy(["telefono" => $form->get("telefono")->getData()]);
            if ($nombreUsado) {
                $this->addFlash('error', 'Ya existe un usuario con ese nombre de usuario.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }
            if ($correoUsado) {
                $this->addFlash('error', 'Ya existe un usuario con ese correo electrónico.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }
            if ($telefonoUsado) {
                $this->addFlash('error', 'Ya existe un usuario con ese teléfono.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
