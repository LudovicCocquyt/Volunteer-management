<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Constraints as Assert;

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
            ->add('firstname')
            ->add('lastname')
            // TODO: Add roles field
            // ->add('roles', ChoiceType::class, [
            //     'label'   => 'Choisir les rôles',
            //     'choices' => [
            //         'User' => 'ROLE_USER',
            //         'Voir les bénévoles'       => 'ROLE_VM_VIEW',
            //         'Gestion des bénévoles'    => 'ROLE_VM_ADMIN',
            //         'Gestion des utilisateurs' => 'ROLE_USER_ADMIN',
            //     ],
            //     'multiple'    => true,
            //     'expanded'    => true,
            //     'choice_attr' => function($choice, $key, $value) { return ['class' => 'w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600']; },
            // ])
            ->add('password', PasswordType::class, [
                'mapped'   => false,
                'attr'     => ['autocomplete' => 'new-password'],
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères.',
                    ]),
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
