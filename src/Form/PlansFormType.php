<?php

namespace App\Form;

use App\Entity\Events;
use App\Entity\Plans;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlansFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startDate', null, [
                'widget' => 'single_text',
            ])
            ->add('endDate', null, [
                'widget' => 'single_text',
            ])
            ->add('nbPers')
            ->add('activityName')
            ->add('event', EntityType::class, [
                'class' => Events::class,
                'choice_label' => 'id',
                'attr' => ['class' => 'hidden'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Plans::class,
        ]);
    }
}