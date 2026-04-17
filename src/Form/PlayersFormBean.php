<?php
namespace App\Form;

use App\Entity\FullnameDE;
use App\Entity\LeagueDE;
use App\Entity\PlayerDE;

/**
 * PlayersFormBean
 */
class PlayersFormBean {
    private array $players = [];

    /**
     * @return array
     */
    public function getPlayers(): array {
        return $this->players;
    }

    /**
     * @param LeagueDE $league
     */
    public function populate(LeagueDE $league): void {
        $this->players = [];

        $x = 15;

        do {
            $player = new PlayerDE();
            $player->setLeague($league);
            $player->setName(new FullnameDE());

            $this->players[] = $player;
        } while (--$x > 0);
    }

    /**
     * @param array $players
     */
    public function setPlayers(array $players): void {
        $this->players = $players;
    }
}
