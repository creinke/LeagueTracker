<?php
namespace App\Form\Type;

use App\Entity\CourseDE;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class CourseType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('name', TextType::class, array('label' => 'Name', 'required' => true, 'attr' => array('class' => 'form-control')))
            ->add('address', AddressType::class, array('label' => false))
            ->add('nines', CollectionType::class, ['entry_type' => NineType::class, 'entry_options' => ['label' => false]])
            ->add('save', SubmitType::class, array('label' => 'Save', 'attr' => array('class' => 'btn btn-primary mt-3', 'style' => 'margin-top: 2em;')));
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => CourseDE::class
        ]);
    }
}