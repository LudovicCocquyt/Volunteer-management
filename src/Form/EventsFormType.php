<?php

namespace App\Form;

use App\Entity\Events;
use EmilePerron\TinymceBundle\Form\Type\TinymceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('description', TinymceType::class, [
                'attr' => [
                    'class' => 'tinymce',
                ]
            ]
            )
            ->add('startAt', null, [
                'widget' => 'single_text',
            ])
            ->add('startCalendar')
            ->add('endCalendar')
            ->add('location')
            ->add('sendingEmail')
            ->add('published')
            ->add('archived')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Events::class,
        ]);
    }
}
