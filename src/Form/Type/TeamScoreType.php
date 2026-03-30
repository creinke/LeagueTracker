<?php
namespace App\Form\Type;

use App\Form\TeamScoreFormBean;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class TeamScoreType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('firstnine', CollectionType::class, ['entry_type' => NumberType::class, 'entry_options' => ['attr' => ['style' => 'height: 2.5em; width: 2.7em; color: black;']],'required' => true])
            ->add('secondnine', CollectionType::class, ['entry_type' => NumberType::class, 'entry_options' => ['attr' => ['style' => 'height: 2.5em; width: 2.7em; color: black;']],'required' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => TeamScoreFormBean::class
        ]);
    }
}