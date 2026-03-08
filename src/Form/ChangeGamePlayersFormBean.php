<?php
namespace App\Form;

use App\Entity\LeagueDE;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * ChangeGamePlayersFormBean
 */
class ChangeGamePlayersFormBean {
	private ArrayCollection $players;

	/**
     * public contructor
     */
    public function __construct(LeagueDE $league, array $players) {
        foreach($players as $player) {
            $id = $player->getId();
            
            foreach($league->getPlayers() as $leaguePlayer) {
                if ($id == $leaguePlayer->getId()) {
                    $this->players[] = $leaguePlayer;
                    break;
                }
            }
        }
    }

    /**
     * @return ArrayCollection of PlayerDE objects
     */
    public function getPlayers(): ArrayCollection {
        return $this->players;
    }

    /**
     * @param ArrayCollection $players
     */
    public function setPlayers( ArrayCollection $players): void {
        $this->players = $players;
    }
}