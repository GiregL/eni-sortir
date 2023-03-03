<?php

namespace App\Form;

use App\Entity\Site;
use App\Model\ProfileUpdateModel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

/**
 * User profile update form.
 */
class ProfileUpdateFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('pseudo', TextType::class, ["label" => "Pseudo", "required" => false])
            ->add('firstName', TextType::class, ["label" => "Prénom", "required" => false])
            ->add('lastName', TextType::class, ["label" => "Nom de famille", "required" => false])
            ->add('phone', TextType::class, ["label" => "Téléphone", "required" => false])
            ->add('email', EmailType::class, ["label" => "E-mail", "required" => false])
            ->add('password', PasswordType::class, ["label" => "Mot de passe", "required" => false])
            ->add('confirmPassword', PasswordType::class, ["label" => "Confirmation du mot de passe", "required" => false])
            ->add('city', EntityType::class, [
                "label" => "Ville de rattachement",
                "class" => Site::class,
                "choice_label" => "name"
            ])
            ->add('nameImage', FileType::class, [
                'label' => false,
                'mapped' => false,
                'required' => false,
                'constraints' => [new Image(['maxSize' => '7024k','mimeTypesMessage' => 'dfghjkl'])]
            ])
            ->add('send', SubmitType::class, ["label" => "Enregistrer"]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            "data_class" => ProfileUpdateModel::class
        ]);
    }


}