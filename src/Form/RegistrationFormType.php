<?php

namespace App\Form;

use App\Entity\Usuario;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username')
            ->add('email', null, [
                'constraints' => [
                    new NotBlank(
                        message: 'Profavor introduce el email',
                    ),

                    new Email(
                        message: 'Email no valido, prueba con algo de este estilo ejemplo@gmail.com',
                    ),
                    new Regex(
                        pattern: '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
                        message: 'El formato del email no es válido',
                    ),
                ]
            ])
            ->add('telefono', null, [
                'constraints' => [
                    new NotBlank(
                        message: 'Profavor introduce el teléfono',
                    ),

                    new Regex(
                        pattern: '/^\d{9}$/',
                        message: 'El teléfono debe contener exactamente 9 dígitos',
                    ),
                ]
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue(
                        message: 'Desbes aceptar los términos',
                    ),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Las contraseñas no coinciden',
                'first_options' => [
                    'label' => 'Contraseña',
                    'attr' => [
                        'class' => 'form-control mb-4'
                    ],
                ],
                'second_options' => [
                    'label' => 'Repetir contraseña',
                    'attr' => [
                        'class' => 'form-control'
                    ],
                ],
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank(
                        message: 'Porfavor introduce la contraseña',
                    ),

                    new Regex(
                        pattern: "/^(?=.*[A-Z])(?=.*\d)(?=.*[!¡¿?*+&%$.,])[^\s]{6,}$/",
                        message: "Debe tener mínimo 6 caracteres, una mayúscula, un número, un símbolo y sin espacios"
                    )
                ],
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Usuario::class,
        ]);
    }
}
