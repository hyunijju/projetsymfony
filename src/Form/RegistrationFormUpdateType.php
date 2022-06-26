<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class RegistrationFormUpdateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('pseudo')
        ->add('prenom')
        ->add('nom')
        ->add('civilite', ChoiceType::class , [
            "choices" => [
                "Femme" => "F",
                "Homme" => "M"
            ],
            "placeholder" => "Choisir..."
        ])
            ->add('email')
            ->add('roles' , ChoiceType::class , [
                "mapped" => false ,
                "required" => false,
                "choices" => [
                    "membre" => "ROLE_MEMBRE",
                    "admin" => "ROLE_ADMIN"
                ],
                "placeholder" => "Choisir...",
                "label" => "Rôle : laisser le champ vide pour ne pas modifier"
            ])
            ->add('plainPassword', PasswordType::class, [
                "mapped" => false , "required" => false , "label" => "Mot de passe : laisser le champ vide pour ne pas modifier"
            ])
            ->add('save', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}