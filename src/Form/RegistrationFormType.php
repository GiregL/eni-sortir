<?php

namespace App\Form;

use App\Entity\Site;
use App\Entity\User;
use App\Model\RegistrationModel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', null, [
                "label" => "Pseudo",
                "label_attr" => ['class' => 'col-xs-4']
            ])
            ->add('email', null, [
                "label" => "Adresse e-mail",
                "label_attr" => ['class' => 'col-xs-4']
            ])
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                "label" => "Mot de passe",
                "label_attr" => ['class' => 'col-xs-4'],
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Renseignez un mot de passe.',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit être d\'au moins {{ limit }} caractères',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('city', EntityType::class, [
                "label" => "Ville de rattachement",
                "label_attr" => ['class' => 'col-xs-4'],
                "class" => Site::class,
                "choice_label" => "name"
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegistrationModel::class,
        ]);
    }
}
