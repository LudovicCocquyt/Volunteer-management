<?php

namespace App\Form;

use App\Entity\Attachment;
use App\Entity\Events;
use EmilePerron\TinymceBundle\Form\Type\TinymceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

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
                'label_attr' => ['class' => 'text-sm text-gray-900'],
            ])
            ->add('startAt', DateTimeType::class, [
                'label'      => 'Date *',
                'label_attr' => ['class' => 'text-sm text-gray-900'],
            ])
        ;

        if ($isEdit) {
            $messageEmail = $event->getMessageEmail();
            // if the messageEmail is written, we keep it, unless it only contains spaces
            if (!$messageEmail || empty(trim(str_replace("\u{A0}", '', html_entity_decode(strip_tags($event->getMessageEmail())))))) {
                // Default message
                $messageEmail =
                    "<p>Bonjour,</p>" .
                    "<p>Merci pour votre inscription à l'événement \"" . $event->getName() . " du " . $event->getStartAt()->format('d/m/Y') . " à " . $event->getStartAt()->format('H:i') . "\".</p>" .
                    "<p>Nous avons bien reçu vos disponibilités et nous vous contacterons bientôt avec plus de détails.</p>" .
                    "<br><p>Nous comptons sur votre participation.</p>" .
                    "<p>En cas de modifications, merci de nous prévenir au plus vite, afin que nous puissions mettre à jour le planning.</p>" .
                    "<br><p>Cordialement,</p>" .
                    "<p>Les bénévoles de l'APEL.</p>";
            }

            $builder
                ->add('description', TinymceType::class, [
                    'attr' => [
                        'toolbar'               => 'undo redo | bold italic underline | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link | code preview',
                        'plugins'               => 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen table  wordcount',
                        'height'                => '400',
                    ]
                ])
                ->add('displayStartAt')
                ->add('schedulingAuto')
                ->add('startCalendar', TimeType::class, [
                    'label'      => 'Début du calendrier',
                    'label_attr' => ['class' => 'text-sm text-gray-900'],
                    'required'   => false,
                ])
                ->add('endCalendar', TimeType::class, [
                    'label'      => 'Fin du calendrier',
                    'label_attr' => ['class' => 'text-sm text-gray-900'],
                    'required'   => false,
                ])
                ->add('location', TextType::class, [
                    'label'      => 'Lieu',
                    'label_attr' => ['class' => 'text-sm text-gray-900'],
                    'required'   => false,
                ])
                ->add('displayLocation')
                ->add('managerNotification', TextType::class, [
                    'label'      => 'Envoi d’un email aux responsables après chaque inscription bénévole',
                    'label_attr' => ['class' => 'text-sm text-gray-900'],
                    'required'   => false,
                ])
                ->add('attachments', EntityType::class, [
                    'class'        => Attachment::class,
                    'label'        => 'Piéce jointe(s)',
                    'label_attr'   => ['class' => 'text-sm text-gray-900'],
                    'choice_label' => 'originalName',
                    'multiple'     => true,
                    'expanded'     => true,
                    'required'     => false,
                ])
                ->add('messageEmail', TinymceType::class, [
                    'label_attr' => ['class' => 'text-sm text-gray-900'],
                    'label'      => 'Contenu de l\'email envoyé aux bénévoles',
                    'data'       => $messageEmail,
                    'attr'       => [
                        'toolbar'               => 'undo redo | bold italic underline | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link | code fullscreen',
                        'plugins'               => 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen table  wordcount',
                        'height'                => '400',
                        ]
                ])
                ->add('published')
                ->add('archived')
                ->add('displayPeopleName')
                ->add('displayCommentForSubscription')
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Events::class,
        ]);
    }
}
