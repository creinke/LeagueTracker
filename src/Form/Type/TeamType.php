<?php
namespace App\Form\Type;

use App\Entity\TeamDE;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TeamType extends AbstractType {
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $playerChoices = array();
        $league = $_SESSION['league'];
        $playerChoices[' '] = NULL;
        
        foreach($league->getPlayers() as $player) {
            $playerChoices[$player->getName()->getFullname()] = $player;
        }

        $builder->add('name', TextType::class, array('label' => 'Team Name', 'required' => false, 'attr' => array('style' => 'height: 2.1em;', 'class' => 'form-control')))
            ->add('teamnumber', NumberType::class, array('label' => 'Team Number', 'required' => false, 'attr' => array('style' => 'height: 2.1em;', 'class' => 'form-control')))
            ->add('players', CollectionType::class, ['entry_type' => ChoiceType::class, 'entry_options' => [ 'choices' => $playerChoices, 'attr' => ['style' => 'height: 2.2em;', 'class' => 'form-control'],],])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => TeamDE::class
        ]);
    }
}