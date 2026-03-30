<?php
namespace App\Form\Type;

use App\Entity\RegionDE;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegionType extends AbstractType {
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder->add('code', TextType::class, array('label' => 'Region', 'required' => true, 'attr' => array('class' => 'form-control')))
            ->add('countryName', TextType::class, array('label' => 'Country', 'required' => true, 'attr' => array('class' => 'form-control')))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => RegionDE::class
        ]);
    }
}