<?php
namespace App\Form\Type;

use App\Form\DataTransformer\BooleanTypeToBooleanTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BooleanType extends AbstractType {
    const int VALUE_FALSE = 0;
    const int VALUE_TRUE = 1;

    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder->addModelTransformer(new BooleanTypeToBooleanTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults(array(
            'compound' => false
        ));
    }

    public function getName(): string {
        return 'boolean';
    }
}