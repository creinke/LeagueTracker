<?php
namespace App\Form\Type;

use App\Form\DataTransformer\BooleanTypeToBooleanTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BooleanType extends AbstractType {
    const VALUE_FALSE = 0;
    const VALUE_TRUE = 1;

    /**
     *
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->addModelTransformer(new BooleanTypeToBooleanTransformer());
    }

    /**
     *
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'compound' => false
        ));
    }

    /**
     *
     * {@inheritdoc}
     */
    public function getName() {
        return 'boolean';
    }
}