<?php

namespace App\Form;

use App\Data\EventFilterData;
use App\Entity\Site;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventFilterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('site', EntityType::class, [
                'label' => 'Site : ',
                'required' => false,
                'class' => Site::class,
                'choice_label' => 'name',
            ])
            
            ->add('event_name', TextType::class, [
                'label' => 'Le nom de la sortie contient : ',
                'required' => false
            ])

            ->add('start_date', DateType::class, [
                'label' => false,
                'required' => false,
                'widget' => 'single_text'
            ])
            ->add('end_date', DateType::class, [
                'label' => false,
                'required' => false,
                'widget' => 'single_text'
            ])

            ->add('is_organizer', CheckboxType::class, [
                'label' => "Sorties dont je suis l'organisateur/trice",
                'required' => false,
            ])
            
            ->add('is_member', CheckboxType::class, [
                'label' => "Sorties auxquelles je suis inscrit/e",
                'required' => false,
            ])

            ->add('is_not_member', CheckboxType::class, [
                'label' => "Sorties auxquelles je ne suis pas inscrit/e",
                'required' => false,
            ])

            ->add('is_passed_event', CheckboxType::class, [
                'label' => "Sorties passées",
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventFilterData::class,
            'method' => 'GET',
            'csrf_protection' => false
        ]);
    }

    public function getBlockPrefix()
    {
        return '';
    }
}
