<?php
namespace App\Form;

use App\Entity\LeagueDE;
use App\Entity\PlayerDE;
use App\Entity\TeamDE;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * TeamsFormBean
 */
class TeamsFormBean {
    /**
     * public contructor
     */
    public function __construct(LeagueDE $league) {
        $this->teams = new ArrayCollection();

        $x = 15;

        do {
            $team = new TeamDE();
            $team->setLeague($league);
            $team->getPlayers()->add(new PlayerDE());
            $team->getPlayers()->add(new PlayerDE());
            $team->getPlayers()->add(new PlayerDE());
            $team->getPlayers()->add(new PlayerDE());
            
            $this->teams->add($team);
        } while (--$x > 0);
    }

    private Collection $teams;

    /**
     * @return Collection
     */
    public function getTeams() : Collection {
        return $this->teams;
    }

    /**
     * @param Collection $teams
     */
    public function setTeams(Collection $teams): void {
        $this->teams = $teams;
    }
}