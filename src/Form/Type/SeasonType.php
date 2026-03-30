<?php
namespace App\Form\Type;

use App\Entity\SeasonDE;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class SeasonType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('name', TextType::class, array('label' => 'Name', 'required' => true, 'attr' => array('class' => 'form-control')))
            ->add('startdate', DateType::class, array('label' => 'Start Date', 'required' => true, 'widget' => 'single_text', 'attr' => array('class' => 'form-control')))
            ->add('enddate', DateType::class, array('label' => 'End Date', 'required' => true, 'widget' => 'single_text', 'attr' => array('class' => 'form-control')))
            ->add('sessions', CollectionType::class, ['entry_type' => SessionType::class, 'entry_options' => ['label' => false]]);
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => SeasonDE::class
        ]);
    }
}