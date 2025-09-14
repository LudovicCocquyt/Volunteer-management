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
                'label' => 'Description de l\'événement:',
                'attr' => [
                    'toolbar'               => 'undo redo | formatselect | bold italic underline | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | code fullscreen',
                    'plugins'               => 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help wordcount',
                    'menubar'               => 'file edit view insert format tools table help',
                    'height'                => '400',
                ]
            ])
            ->add('startAt', null, [
                'widget' => 'single_text',
            ])
            ->add('displayStartAt', )
            ->add('schedulingAuto')
            ->add('startCalendar')
            ->add('endCalendar')
            ->add('location')
            ->add('displayLocation')
            ->add('sendingEmail')
            ->add('published')
            ->add('archived')
            ->add('displayPeopleName')
            ->add('displayCommentForSubscription')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Events::class,
        ]);
    }
}
