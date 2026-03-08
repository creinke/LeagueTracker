<?php
namespace App\Form;

use App\Entity\FullnameDE;
use App\Entity\LeagueDE;
use App\Entity\PlayerDE;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;

/**
 * PlayersFormBean
 */
class PlayersFormBean {
    private $players;

    /**
     * @return PersistentCollection
     */
    public function getPlayers() {
        return $this->players;
    }

    /**
     * @param EntityManagerInterface $em
     * @param LeagueDE $league
     */
    public function populate(EntityManagerInterface $em, LeagueDE $league) {
        $this->setPlayers(new PersistentCollection($em, new ClassMetadata('App\Entity\PlayerDE'), new ArrayCollection()));
        
        $x = 15;
        
        do {
            $player = new PlayerDE($em);
            $player->setLeague($league);
            $player->setName(new FullnameDE());
            
            $this->players->add($player);
        } while (--$x > 0);
    }
    
    /**
     * @param PersistentCollection $players
     */
    public function setPlayers($players) {
        $this->players = $players;
    }
}