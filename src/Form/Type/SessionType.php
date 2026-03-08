<?php
namespace App\Form\Type;

use App\Entity\SessionDE;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class SessionType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('name', TextType::class, array('label' => 'Name', 'required' => true, 'attr' => array('class' => 'form-control')))
            ->add('startdate', DateType::class, array('label' => 'Start Date', 'required' => true, 'widget' => 'single_text', 'attr' => array('class' => 'form-control')))
            ->add('enddate', DateType::class, array('label' => 'End Date', 'required' => true, 'widget' => 'single_text', 'attr' => array('class' => 'form-control')))
            ->add('events', CollectionType::class, ['entry_type' => EventType::class, 'entry_options' => ['label' => false]]);
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => SessionDE::class,
            'useSession' => true
        ]);
    }
}