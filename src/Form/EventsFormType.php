<?php

namespace App\Form;

use App\Entity\Events;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use EmilePerron\TinymceBundle\Form\Type\TinymceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Events $event */
        $event  = $options['data'];
        $isEdit = $event && $event->getId() !== null;

        $builder
            ->add('name', TextType::class, [
                'label'      => 'Nom *',
                'label_attr' => ['class' => 'text-sm text-gray-900 dark:text-neutral-400'],
            ])
            ->add('startAt', DateTimeType::class, [
                'label'      => 'Date *',
                'label_attr' => ['class' => 'text-sm text-gray-900 dark:text-neutral-400'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Events::class,
        ]);
    }
}
