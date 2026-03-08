<?php
namespace App\View;

use App\Entity\PlayerDE;

/**
 * PlayerHandicapViewBean
 * @author Kurt Reinke
 */
class PlayerHandicapViewBean {
    private ?int $handicap;
    private float $handicapIndex;
    private PlayerDE $player;
    private array $scores;
    private array $scoresUsedFlag;

    /**
     * public contructor
     */
    public function __construct(PlayerDE $player, float $handicapIndex, ?int $handicap, array $scores, array $scoresUsed) {
        $this->player = $player;
        $this->handicapIndex = $handicapIndex;
        $this->handicap = $handicap;
        $this->scores = $scores;
	    $this->scoresUsedFlag = array();

        for ($scoreIndex = 0; $scoreIndex < sizeof($scores); $scoreIndex++) {
            $score = $scores[$scoreIndex];
            $this->scoresUsedFlag[$scoreIndex] = in_array($score, $scoresUsed);
        }
    }

    public function getHandicap(): ?int {
		return $this->handicap;
	}

	public function getHandicapIndex() : float {
		return $this->handicapIndex;
	}

	public function getPlayer() : PlayerDE {
        return $this->player;
    }

    public function getScores() : array {
        return $this->scores;
    }

    public function getScoresUsedFlag() : array {
        return $this->scoresUsedFlag;
    }

    public function setHandicap(int $handicap): void {
		$this->handicap = $handicap;
	}

	public function setHandicapIndex(float $handicapIndex): void {
		$this->handicapIndex = $handicapIndex;
	}

	public function setPlayer(PlayerDE $player): void {
        $this->player = $player;
    }

    public function setScores($scores) : void {
        $this->scores = $scores;
    }

    public function setScoresUsedFlag(array $scoresUsedFlag): void {
        $this->scoresUsedFlag = $scoresUsedFlag;
    }
}
