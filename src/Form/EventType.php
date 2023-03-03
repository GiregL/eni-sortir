<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Place;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la sortie : ',
                'required' => false
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'Date et heure de la sortie : ',
                'widget' => 'single_text'
            ])
            ->add('dateLimitRegister', DateType::class, [
                'label' => 'Date limite d\'inscription : ',
                'widget' => 'single_text'
            ])
            ->add('MaxRegister', NumberType::class, [
                'label' => 'Nombre de places : ',
            ])
            ->add('duration', NumberType::class, [
                'label' => 'Durée : ',
            ])
            ->add('eventInfos', TextareaType::class, [
                'label' => 'Description et Infos : ',
            ])
            ->add('place', EntityType::class, [
                'label' => 'Place : ',
                'class' => Place::class,
                'choice_label' => 'name',
            ])

            ->add('save', SubmitType::class, ["label" => "Créer"])
            ->add('saveAndPublish', SubmitType::class, ['label' => 'Publier'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return '';
    }
}
