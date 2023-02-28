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
                        // TODO: Contraintes de type pétées
//                        "maxSize" => "1m",
//                        "maxSizeMessage" => "Le fichier fourni dépasse la taille limite de 1024ko.",
//                        "mimeTypes" => [
//                            "text/csv",
//                            "application/vnd.ms-excel"
//                        ],
//                        "mimeTypesMessage" => "Vous devez fournir un fichier au format CSV."
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