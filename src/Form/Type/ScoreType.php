<?php
namespace App\Form\Type;

use App\Entity\TeeDE;
use App\Form\ScoreBean;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class ScoreType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('strokes', CollectionType::class, ['entry_type' => NumberType::class, 'entry_options' => ['attr' => ['style' => 'height: 2.5em; width: 2.7em; color: black;']],'required' => true]);

            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $formEvent) {
            	// Get the current form
            	$form = $formEvent->getForm();
            	// Get the data for this form (in this case it's the sub form's entity) not the main form's entity.
            	$scoreBean = $formEvent->getData();
            	
            	$form
                    ->add('tee', ChoiceType::class, ['label' => 'Tee', 'required' => true, 'attr' => ['class' => 'form-control', 'style' => 'height: 2.8em;'],
            		    'choices' => $scoreBean->getTees(), 'choice_label' => function(TeeDE $tee, $key, $value) {return $tee->getName();}]);
            });
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => ScoreBean::class
        ]);
    }
}