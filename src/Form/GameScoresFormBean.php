<?php

namespace App\Form;

use App\Entity\EventDE;
use App\Entity\GameDE;
use App\Entity\NineDE;
use App\Entity\PlayerDE;
use App\Entity\PlayermatchDE;
use App\Entity\ScoreDE;
use App\Model\EventType;
use App\Model\EventFormatType;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * GameScoresFormBean
 */
class GameScoresFormBean {
	private ArrayCollection $ninesPlayed;
	private ArrayCollection $playerScores;

	/**
     * public contructor
     */
    public function __construct(EventDE $event, GameDE $game) {
    	$ninesPlayed = new ArrayCollection();
    	$ninesPlayed[] = $event->getNine();

    	if ($event->getSecondnine() != null) {
    		$ninesPlayed[] = $event->getSecondnine();
    	}
    	$this->ninesPlayed = $ninesPlayed;
    	$teeName = $event->getTee()->getName();
    	
    	$singlesMatch = EventType::isSinglesMatch($event->getEventtype());
    	$matchPlay = EventFormatType::isMatchPlay($event->getFormat());
    	
    	if ($singlesMatch) {
    	    $teamOnePlayingPartners = null;
    	    $teamTwoPlayingPartners = null;
    	} else {
        	$teamOnePlayingPartners = [];
        	$teamTwoPlayingPartners = [];
        	foreach($game->getPlayermatches() as $playerMatch) {
        		$teamOnePlayingPartners[] = $playerMatch->getPlayerone();
        		$teamTwoPlayingPartners[] = $playerMatch->getPlayertwo();
        	}
    	}
    	$this->playerScores = new ArrayCollection();
    	
    	if ($singlesMatch) {
    	    if ($matchPlay) {
    	        foreach($game->getPlayermatches() as $playerMatch) {
    	            $this->initializePlayerScores($playerMatch, $playerMatch->getPlayerone(), $teamOnePlayingPartners, $playerMatch->getPlayerOneScores(), $ninesPlayed, $teeName);
    	            $this->initializePlayerScores($playerMatch, $playerMatch->getPlayertwo(), $teamTwoPlayingPartners, $playerMatch->getPlayerTwoScores(), $ninesPlayed, $teeName);
    	        }
    	    } else {
    	        foreach($game->getPlayers() as $player) {
    	            $this->initializePlayerScores(null, $player, null, $game->playerScores($player), $ninesPlayed, $teeName);
    	        }
    	    }
    	} else {
        	foreach($game->getPlayermatches() as $playerMatch) {
        	    $this->initializePlayerScores($playerMatch, $playerMatch->getPlayerone(), $teamOnePlayingPartners, $playerMatch->getPlayerOneScores(), $ninesPlayed, $teeName);
        	    $this->initializePlayerScores($playerMatch, $playerMatch->getPlayertwo(), $teamTwoPlayingPartners, $playerMatch->getPlayerTwoScores(), $ninesPlayed, $teeName);
        	}
    	}
    }

    /**
     * @return number number of nines played
     */
    public function getNinesPlayed() {
    	return $this->ninesPlayed;
    }
    
    /**
     * @return ArrayCollection
     */
    public function getPlayerScores() {
        return $this->playerScores;
    }
    
    /**
     * Retrun the TeeDE entity associated with this Nine and tee name
     * 
     * @param NineDE $nine
     * @param string $teeName
     * @return object
     */
    private function getTee(NineDE $nine, $teeName) {
        foreach($nine->getTees() as $tee) {
            if ($tee->getName() == $teeName) {
                return $tee;
            }
        }
    }

    private function initializePlayerScores(?PlayermatchDE $playerMatch, PlayerDE $player, ?array $teamPlayingPartners, ?ArrayCollection $scores, $ninesPlayed, $teeName) {
        if ($teamPlayingPartners == null) {
            $playingPartners = null;
        } else {
            $playingPartners = [];
    		
    		foreach($teamPlayingPartners as $playingPartner) {
    			if ($playingPartner->getId() != $player->getId()) {
    				$playingPartners[] = $playingPartner;
    			}
    		}
        }
        if (empty($scores) || $scores->count() == 0) {
        	foreach($ninesPlayed as $nine) {
        	    $tee = $this->getTee($nine, $teeName);
        	    $tees = $tee->getNine()->getTees();
        	    $this->playerScores[] = new ScoreBean($playerMatch, $player, $playingPartners, null, false, $tees, $tee, array(null, null, null, null, null, null, null, null, null));
        	}
        } else {
        	foreach($scores as $score) {
        	    $tee = $score->getTee();
        	    $tees = $tee->getNine()->getTees();
        	    $this->playerScores[] = new ScoreBean($playerMatch, $player, $playingPartners, $score, $score->getDuplicatescore(), $tees, $tee, ScoreDE::unpack($score->getStrokes()));
        	}
        }
    }
    
    /**
     * @param number $ninesPlayed
     */
    public function setNinesPlayed($ninesPlayed) {
    	$this->ninesPlayed = $ninesPlayed;
    }
}