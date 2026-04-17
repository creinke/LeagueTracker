<?php
namespace App\Form;

use Doctrine\Common\Collections\Collection;
use App\Entity\PlayerDE;
use App\Entity\PlayermatchDE;
use App\Entity\ScoreDE;
use App\Entity\TeeDE;


class ScoreBean {
    private $duplicate;
    private $partial;
    private $played;
    private $player;
    private $playerMatch;
    private $playingPartners;
    private $score;
    private $strokes;
    private $substitutePlayer;
    private $tee;
    private $tees;
    
   	public function __construct(?PlayermatchDE $playerMatch, PlayerDE $player, ?array $playingPartners, ?ScoreDE $score, $duplicate, Collection $tees, TeeDE $tee, array $strokes) {
        $this->playerMatch = $playerMatch;
        $this->player = $player;
        $this->playingPartners = $playingPartners;
        $this->score = $score;
        $this->duplicate = $duplicate;
        $this->tee = $tee;
        $this->tees = $tees;
        $this->strokes = $strokes;
    }
    
    /**
     * Find a playing parnter's score to use, then update score with playing partner's score and mark as a duplicate score
     * 
     * @param GameScoresFormBean $formbean
     */
    public function duplicatePlayingPartnerScore(GameScoresFormBean $formbean) {
        foreach($this->playingPartners as $playingPartner) {
            foreach($formbean->getPlayerScores() as $playerScore) {
                if ($playingPartner->getId() == $playerScore->getPlayer()->getId() && $playerScore->getPlayed() && $playerScore->getTee()->getId() == $this->tee->getId()) {
                    $this->updateScore($playingPartner, $playerScore->getStrokes(), true);
                    return;
                }
            }
        }
    }
    
    /**
     * @return boolean duplicate score
     */
    public function getDuplicate() {
        return $this->duplicate;
    }

    /**
     * @return boolean partial score
     */
    public function getPartial() {
        return $this->partial;
    }

    /**
     * @return boolean played 
     */
    public function getPlayed() {
        return $this->played;
    }

    /**
     * @return \App\Entity\PlayerDE
     */
    public function getPlayer() {
        return $this->player;
    }

    /**
     * @return \App\Entity\PlayermatchDE
     */
    public function getPlayerMatch() {
        return $this->playerMatch;
    }
    
    /**
     * @return array playing partners
     */
    public function getPlayingPartners() {
        return $this->playingPartners;
    }

    /**
     * @return \App\Entity\ScoreDE
     */
    public function getScore() {
        return $this->score;
    }
    
    /**
     * @return array strokes
     */
    public function getStrokes() {
        return $this->strokes;
    }

    /**
     * @return \App\Entity\PlayerDE
     */
    public function getSubstitutePlayer() {
        return $this->substitutePlayer;
    }
    
    /**
     * @return \App\Entity\TeeDE
     */
    public function getTee() {
        return $this->tee;
    }
    
    /**
   	 * @return Collection of \App\Entity\TeeDE
   	 */
   	public function getTees() {
   		return $this->tees;
   	}
    
    /**
     * @param array $score
     * @return boolean if partial score
     */
    private function isPartial() {
        if ($this->isPlayed()) {
            for ($i = 0; $i < sizeof($this->strokes); $i++) {
                if ($this->strokes[$i] == 0) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * @param array $score
     * @return boolean if player played
     */
    private function isPlayed() {
        for ($i = 0; $i < sizeof($this->strokes); $i++) {
            if ($this->strokes[$i] != 0) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * @param boolean $duplicate
     */
    public function setDuplicate($duplicate) {
        $this->duplicate = $duplicate;
    }

    /**
     * @param \App\Entity\TeeDE $tee
     */
    public function setTee($tee) {
        $this->tee = $tee;
    }
    
    /**
     * @param boolean $partial
     */
    public function setPartial($partial) {
        $this->partial = $partial;
    }

    /**
     * @param boolean $played
     */
    public function setPlayed($played) {
        $this->played = $played;
    }

    /**
     * @param object PlayerDE $player
     */
    public function setPlayer($player) {
        $this->player = $player;
    }

    /**
     * @param \App\Entity\PlayermatchDE $playerMatch
     */
    public function setPlayerMatch($playerMatch) {
        $this->playerMatch = $playerMatch;
    }
    
    /**
     * @param array PlayerDE $playingPartners
     */
    public function setPlayingPartners($playingPartners) {
        $this->playingPartners = $playingPartners;
    }

    /**
     * @param \App\Entity\ScoreDE $score
     */
    public function setScore($score) {
        $this->score = $score;
    }
    
    /**
     * @param array int $strokes
     */
    public function setStrokes($strokes) {
        $this->strokes = $strokes;
    }
    
    /**
   	 * @param Collection of \App\Entity\TeeDE $tees
   	 */
   	public function setTees($tees) {
   		$this->tees = $tees;
   	}
    
    /**
     * @param \App\Entity\PlayerDE $substitutePlayer
     */
    public function setSubstitutePlayer($substitutePlayer) {
        $this->substitutePlayer = $substitutePlayer;
    }
    
    /**
     * Updates the ScoreNean based on criteria passed
     * 
     * @param PlayerDE $substitutePlayer
     * @param array $strokes
     * @param boolean $duplicateScore
     */
    public function updateScore(?PlayerDE $substitutePlayer, array $strokes, $duplicateScore) {
        if (!empty($substitutePlayer)) {
           $this->substitutePlayer = $substitutePlayer; 
        }
        $this->strokes = $strokes;
        $this->duplicate = $duplicateScore;
        $this->played = true;
    }
    
    /**
     * Update the state of the ScoreBean as appropriate
     */
    public function updateState() {
        if ($this->getDuplicate()) {
            $this->substitutePlayer = $this->getPlayer();
        }
        $this->played = $this->isPlayed(); 
        $this->partial = $this->isPartial();
    }
}