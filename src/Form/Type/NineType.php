<?php
namespace App\Form\Type;

use App\Entity\NineDE;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NineType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('name', TextType::class, array('required' => true, 'attr' => array('class' => 'form-control')))
            ->add('tees', CollectionType::class, ['entry_type' => TeeType::class, 'entry_options' => ['label' => false]]);
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => NineDE::class
        ]);
    }
}