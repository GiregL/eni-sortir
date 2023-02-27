<?php

namespace App\Form;

use App\Model\BatchAddUsersModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class BatchAddUsersFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add("usersFile", FileType::class, [
                "label" => "Fichier CSV",
                "required" => true,
                "constraints" => [
                    new File([
                        "maxSize" => "1024k",
                        "maxSizeMessage" => "Le fichier fourni dépasse la taille limite de 1024ko.",
                        "mimeTypes" => [
                            "application/csv",
                            "application/x-csv"
                        ],
                        "mimeTypesMessage" => "Vous devez fournir un fichier au format CSV."
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
                "data_class" => BatchAddUsersModel::class
        ]);
    }
}