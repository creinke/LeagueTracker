<?php
namespace App\Form;

use App\Entity\LeagueDE;
use Doctrine\Common\Collections\ArrayCollection;
use DateTime;

/**
 * GameFormBean
 */
class GameFormBean {
    private array $players;
    private DateTime $startingtime;

    /**
     * public contructor
     */
    public function __construct(LeagueDE $league, array $players, DateTime $startingtime) {
        $this->setStartingtime($startingtime);
        
        foreach($players as $player) {
            $id = $player->getId();
            
            if ($id == null) {
                $this->players[] = $player;
            } else {
                foreach($league->getPlayers() as $leaguePlayer) {
                    if ($id == $leaguePlayer->getId()) {
                        $this->players[] = $leaguePlayer;
                        break;
                    }
                }
            }
        }
    }

    /**
     * @return array of \App\Entity\PlayerDE objects
     */
    public function getPlayers(): array {
        return $this->players;
    }

    /**
     * @return DateTime <DateTime, NULL> starting time of game
     */
    public function getStartingtime(): DateTime {
        return $this->startingtime;
    }

    /**
     * @param array $players of \App\Entity\PlayerDE of objects $players
     */
    public function setPlayers(array $players): void {
        $this->players = $players;
    }

    /**
     * @param DateTime $startingtime <DateTime, NULL> $startingtime
     */
    public function setStartingtime( DateTime $startingtime): void {
        $this->startingtime = $startingtime;
    }
}