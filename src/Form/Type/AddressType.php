<?php
namespace App\Form\Type;

use App\Entity\AddressDE;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressType extends AbstractType {
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder->add('addressline1', TextType::class, array('label' => 'Address Line One', 'required' => true, 'attr' => array('class' => 'form-control')))
            ->add('addressline2', TextType::class, array('label' => 'Address Line Two', 'required' => false, 'attr' => array('class' => 'form-control')))
            ->add('city', TextType::class, array('label' => 'City', 'required' => true, 'attr' => array('class' => 'form-control')))
            ->add('postalcode', TextType::class, array('label' => 'Postal Code', 'required' => true, 'attr' => array('class' => 'form-control')))
            ->add('region', RegionType::class, array('label' => false))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => AddressDE::class
        ]);
    }
}