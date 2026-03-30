<?php
namespace App\Form\Type;

use App\Entity\TeeDE;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TeeType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder->add('name', TextType::class, array('required' => false, 'attr' => array('class' => 'form-control')))
            ->add('holes', CollectionType::class, ['entry_type' => HoleType::class, 'entry_options' => ['label' => false]]);
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => TeeDE::class
        ]);
    }
}