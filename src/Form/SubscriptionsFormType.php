<?php

namespace App\Form;

use App\Entity\Events;
use App\Entity\Plans;
use App\Entity\Subscriptions;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubscriptionsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', null, [
                'label' => 'Prénom'
            ])
            ->add('lastname', null, [
                'label' => 'Nom'
            ])
            ->add('email')
            ->add('phone', null, [
                'label' => 'Téléphone'
            ])
            ->add('childName',  null, [
                'label' => 'Nom de l\'enfant'
            ])
            ->add('class', ChoiceType::class, [
                'label'   => 'Classe de l\'enfant',
                'choices' => [
                    '---'             => '---',
                    'Petite section'  => 'Petite section',
                    'Moyenne section' => 'Moyenne section',
                    'Grande section'  => 'Grande section',
                    'CP'              => 'CP',
                    'CE1'             => 'CE1',
                    'CE2'             => 'CE2',
                    'CM1'             => 'CM1',
                    'CM2'             => 'CM2',
                    '6e'              => '6e',
                    '5e'              => '5e',
                    '4e'              => '4e',
                    '3e'              => '3e'
                ]
            ])
            ->add('event', HiddenType::class, [])
            ->add('availabilities', HiddenType::class, [])
            ->add('comment', null, [
                'label'    => 'Commentaire',
                'attr' => array(
                    'placeholder' => 'Vous avez une préférence de stand? Vous souhaitez être sur le même stand qu\'un autre bénévole? Vous avez des impératifs (enfants...)?
                    N\'hésitez pas à nous en faire part, nous ferons notre possible pour répondre à vos attentes.'
                )
            ])
            // ->add('plan', EntityType::class, [
            //     'class'        => Plans::class,
            //     'choice_label' => function ($plan) {
            //         return $plan->getStartDate()->format('H:i')  ." - ". $plan->getEndDate()->format('H:i');
            //     },
            //     'required'     => false,
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Subscriptions::class,
        ]);
    }
}
