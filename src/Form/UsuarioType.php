<?php

namespace App\Form;

use App\Entity\Rol;
use App\Entity\Usuario;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class UsuarioType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username')
             ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Las contraseñas no coinciden',
                'required' => false,
                'first_options' => [
                    'label' => 'Contraseña',
                    'attr' =>[
                        'class' => 'form-control mb-4'
                    ],
                ],
                'second_options' => [
                    'label' => 'Repetir contraseña',
                    'attr' =>[
                        'class' => 'form-control'
                    ],
                ],
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [                    
                    new Regex(
                        pattern: "/^(?=.*[A-Z])(?=.*\d)(?=.*[!¡¿?*+&%$.,])[^\s]{6,}$/",
                        message: "Debe tener mínimo 6 caracteres, una mayúscula, un número, un símbolo y sin espacios"
                    )
                ],
            ])
            ->add('telefono')
            ->add('email', EmailType::class, [
                'attr' => ['autocomplete' => 'email'],
                'constraints' => [
                    new NotBlank(
                        message:'Por favor, introduce tu email',
                    ),
                    new Email(
                        message: 'Por favor, introduce un email válido',
                    ),
                    new Regex(
                        pattern: '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
                        message: 'El formato del email no es válido',
                    ),
                ],
            ])
            ->add('rol', ChoiceType::class, [
                "choices" => [
                    "Administrador usuarios" => Rol::ADMIN,
                    "Administrador coches" => Rol::ADMIN_COCHE,
                    "Usuario" => Rol::USER
                ]
            ])
            ->add('fotoPerfil', FileType::class, [
                'label' => 'Foto de Perfil (JPG, PNG)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image([
                        'maxSize' => '1024k',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                    ])
                ],
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Usuario::class,
        ]);
    }
}
