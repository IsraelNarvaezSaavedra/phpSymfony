<?php

namespace App\Form;

use App\Form\OpcionDesplegableType;
use App\Entity\ConfiguracionLlamada;
use App\Enum\TipoInteraccion;
use App\Enum\TipoUsuario;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Validator\Constraints\Length;

use function Symfony\Component\Translation\t;

class ConfigCallType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tipoLlamada', EnumType::class, [
                'class' => TipoUsuario::class,
                'expanded' => true,
                'multiple' => false,
                'label' => false,
                'required' => true,
                'empty_data' => TipoUsuario::ANONIMO,
                'data' => $builder->getData()->getTipoLlamada() ?? TipoUsuario::ANONIMO,
            ])
            ->add('tipoInteraccion', EnumType::class, [
                'class' => TipoInteraccion::class,
                'expanded' => true,
                'multiple' => false,
                'label' => false,
                'required' => true,
                'empty_data' => TipoInteraccion::IA,
                'data' => $builder->getData()->getTipoInteraccion() ?? TipoInteraccion::IA,
            ])
            ->add('prompt', TextareaType::class, [
                'label' => 'Cerebro de la IA',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Defina el comportamiento y personalidad de la ia a traves del prompt',
                ],
                'constraints' => [
                    new Length([
                        'min' => 20,
                        'max' => 10000,
                    ]),
                ],
                
            ])
            ->add('opcionDesplegable', CollectionType::class, [
                'entry_type' => OpcionDesplegableType::class,
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
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
