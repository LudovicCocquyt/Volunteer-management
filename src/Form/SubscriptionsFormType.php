<?php

namespace App\Form;

use App\Entity\Events;
use App\Entity\Subscriptions;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubscriptionsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', null, [
                "attr" => [
                    "placeholder" => "Prénom"
                ]
            ])
            ->add('lastname', null, [
                "attr" => [
                    "placeholder" => "Nom"
                ]
            ])
            ->add('email', EmailType::class, [
                "attr" => [
                    "placeholder" => "Email"
                ]
            ])
            ->add('phone', TelType::class, [
                "attr" => [
                    "placeholder" => "Téléphone"
                ]
            ])
            ->add('childName',  null, [
                "attr" => [
                    "placeholder" => "Prénom de l'enfant"
                ]
            ])
            ->add('class', ChoiceType::class, [
                'choices' => [
                    'Classe de l\'enfant' => '---',
                    'Petite section'      => 'Petite section',
                    'Moyenne section'     => 'Moyenne section',
                    'Grande section'      => 'Grande section',
                    'CP'                  => 'CP',
                    'CE1'                 => 'CE1',
                    'CE2'                 => 'CE2',
                    'CM1'                 => 'CM1',
                    'CM2'                 => 'CM2',
                    '6e'                  => '6e',
                    '5e'                  => '5e',
                    '4e'                  => '4e',
                    '3e'                  => '3e'
                ]
            ])
            // ->add('event', HiddenType::class, [
            //     'data'   => json_encode($options['data']->getEvent() ?? []),
            // ])
            ->add('event', EntityType::class, [
                'class' => Events::class,
                'choice_label' => 'name',
              ])
            ->add('availabilities', HiddenType::class, [
                'data'   => json_encode($options['data']->getAvailabilities() ?? []),
                'mapped' => false // facultatif selon si tu veux lier à l'entité ou non
            ])
            ->add('comment', null, [
                'attr' => array(
                    'placeholder' => 'Vous avez une préférence de stand? Vous souhaitez être sur le même stand qu\'un autre bénévole? Vous avez des impératifs (enfants...)?
                    N\'hésitez pas à nous en faire part, nous ferons notre possible pour répondre à vos attentes.'
                )
            ])
            ->add('captcha', Recaptcha3Type::class, [
                'constraints' => new Recaptcha3(),
                'action_name' => 'SubscriptionsForm',
                'locale'      => 'fr',
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Subscriptions::class,
        ]);
    }
}
