<?php
namespace App\Form\Type;

use App\Entity\PlayerDE;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlayerType extends AbstractType {
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void {

        $builder->add('firstname', TextType::class, array('label' => 'First Name', 'required' => false, 'attr' => array('class' => 'form-control', 'style' => 'height: 2.1em;')))
            ->add('middlenameorinitial', TextType::class, array('label' => 'Middle Name or Initial', 'required' => false, 'attr' => array('class' => 'form-control', 'style' => 'height: 2.1em;')))
            ->add('lastname', TextType::class, array('label' => 'Last Name', 'required' => false, 'attr' => array('class' => 'form-control', 'style' => 'height: 2.1em;')))
            ->add('generation', ChoiceType::class, array('label' => 'Generation', 'required' => false, 'attr' => array('class' => 'form-control', 'style' => 'height: 2.1em;'),
                'choices'  => array('' => '', 'JR' => 'JR', 'SR' => 'SR', "III" => 'III')))
            ->add('type', ChoiceType::class, array('label' => 'Player Type', 'required' => true, 'attr' => array('class' => 'form-control', 'style' => 'height: 2.1em;'),
                'choices'  => array('Regular' => 'REGULAR', 'Sub' => 'SUB')))
            ->add('seedhandicapindex', NumberType::class, array('scale' => 2, 'label' => 'Seed Handicap Index', 'required' => false, 'attr' => array('class' => 'form-control')))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => PlayerDE::class
        ]);
    }
}