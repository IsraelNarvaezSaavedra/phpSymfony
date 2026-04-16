<?php

namespace App\Form;

use App\Form\OpcionDesplegableType;
use App\Entity\ConfiguracionLlamada;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Validator\Constraints\Length;

class ConfigCallType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tipoLlamada', ChoiceType::class, [
                'choices' => [
                    'Anónimo' => 'anonimo',
                    'No cliente' => 'no_cliente',
                    'Cliente' => 'cliente',
                ],
                'expanded' => true,
                'multiple' => false,
                'label' => false,
                'required' => true,
            ])
            ->add('tipoInteraccion', ChoiceType::class, [
                'choices' => [
                    'Inteligencia Artificial (IVA)' => 'IVA',
                    'Menú de Teclas (IVR)' => 'IVR',
                ],
                'expanded' => true,
                'multiple' => false,
                'label' => false,
                'required' => true,
            ])
            ->add('prompt', TextareaType::class, [
                'label' => 'Cerebro de la IA',
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Defina el comportamiento y personalidad de la ia a traves del prompt',
                ],
                'constraints' => [
                    new Length([
                        'min' => 20,
                        'max' => 2000,
                    ]),
                ],
                
            ])
            ->add('opcionDesplegable', CollectionType::class, [
                'entry_type' => OpcionDesplegableType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => 'Opciones desplegables',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConfiguracionLlamada::class,
        ]);
    }
}
