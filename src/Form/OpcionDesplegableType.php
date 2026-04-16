<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Validator\Constraints\Choice;


class OpcionDesplegableType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tecla', TextType::class, [
                'label' => 'Tecla',
                'attr' => [
                    'min' => 0,
                    'max' => 9,
                    'class' => 'tecla_num',
                    'placeholder' => '#',
                ],
            ])
            ->add('label', TextType::class, [
                'label' => 'Etiqueta',
                'attr' => [
                    'placeholder' => 'Nombre de la opción',
                    'class' => 'etiqueta',
                ],
            ])
            ->add('desplegable', ChoiceType::class, [
                'choices' => [
                    'Seleccione una opción' => '',
                    'Trasferir llamada a un agente' => 'transferir',
                    'Mensaje personalizado' => 'mensaje',
                    'Submenú' => 'submenu',
                ],
                'expanded' => false,
                'multiple' => false,
                'constraints' => [
                    new Choice([
                        'choices' => ['','transferir', 'mensaje', 'submenu'],
                        'message' => 'Seleccione una opción válida.',
                    ]),
                ],
                'attr' => ['class' => 'bloque-menu']
            ])

            //Campos adicionales dependiendo de la opción seleccionada
            ->add('numeroAgente', TextType::class, [
                'label' => 'Número del agente',
                'required' => false,
                'attr' => [
                    'placeholder' => '+34...',
                    'class' => 'bloque_agente',
                ],
            ])
            ->add('mensajePersonalizado', TextareaType::class, [
                'label' => 'Mensaje personalizado',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ingrese el mensaje a reproducir',
                    'class' => 'bloque_mensaje',
                ],
            ])
        ;
        $nivelActual = $options['nivel'];
        if ($nivelActual <= 3) {
            $builder->add('submenu', CollectionType::class, [
                'entry_type' => self::class,
                'allow_add' => true,
                'prototype' => true,
                'prototype_name' => '__name' . $nivelActual .'__',
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
                'entry_options' => [
                    'nivel' => $nivelActual + 1,
                ],
                'attr' => ['class' => 'typeSubmenu'],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'nivel' => 1,
        ]);
    }
}
