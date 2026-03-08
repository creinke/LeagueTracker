<?php
namespace App\Form;

use App\Entity\LeagueDE;
use App\Entity\PlayerDE;
use App\Entity\TeamDE;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;

/**
 * TeamsFormBean
 */
class TeamsFormBean {
    /**
     * public contructor
     */
    public function __construct(EntityManagerInterface $em, LeagueDE $league) {
        $this->setTeams(new PersistentCollection($em, new ClassMetadata('App\Entity\TeamDE'), new ArrayCollection()));

        $x = 15;

        do {
            $team = new TeamDE($em);
            $team->setLeague($league);
            $team->getPlayers()->add(new PlayerDE($em));
            $team->getPlayers()->add(new PlayerDE($em));
            $team->getPlayers()->add(new PlayerDE($em));
            $team->getPlayers()->add(new PlayerDE($em));
            
            $this->teams->add($team);
        } while (--$x > 0);
    }

    private $teams;

    /**
     * @return PersistentCollection
     */
    public function getTeams() {
        return $this->teams;
    }

    /**
     * @param PersistentCollection $teams
     */
    public function setTeams($teams) {
        $this->teams = $teams;
    }
}