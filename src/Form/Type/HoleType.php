<?php
namespace App\Form\Type;

use App\Entity\HoleDE;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HoleType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder->add('handicap', NumberType::class, array('required' => false, 'attr' => array('class' => 'form-control')))
            ->add('holenumber', NumberType::class, array('required' => false, 'attr' => array('class' => 'form-control')))
            ->add('length', NumberType::class, array('required' => false, 'attr' => array('class' => 'form-control')))
            ->add('name', TextType::class, array('required' => false, 'attr' => array('class' => 'form-control')))
            ->add('par', NumberType::class, array('required' => false, 'attr' => array('class' => 'form-control')));
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => HoleDE::class
        ]);
    }
}