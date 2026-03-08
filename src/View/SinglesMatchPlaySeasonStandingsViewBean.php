<?php
namespace App\View;

class SinglesMatchPlaySeasonStandingsViewBean {
    private array $seasonPoints;
    private array $sessionPoints;

	public function __construct() {
		$this->seasonPoints = [];
		$this->sessionPoints = [];
	}

	/**
     * @return array:
     */
    public function getSeasonPoints(): array {
        return $this->seasonPoints;
    }

    /**
     * @return array:
     */
    public function getSessionPoints(): array {
        return $this->sessionPoints;
    }

    /**
     * @param array $seasonPoints : $seasonPoints
     */
    public function setSeasonPoints(array $seasonPoints): void {
        $this->seasonPoints = $seasonPoints;
    }

    /**
     * @param array $sessionPoints : $sessionPoints
     */
    public function setSessionPoints(array $sessionPoints): void {
        $this->sessionPoints = $sessionPoints;
    }

    public function updatePlayerPoints(SinglesMatchPlayEventViewBean &$singlesMatchPlayEventViewBean): void {
        for ($playerIndex = 0; $playerIndex < sizeof($singlesMatchPlayEventViewBean->players); $playerIndex++) {
            $player = $singlesMatchPlayEventViewBean->players[$playerIndex];
            $points = $player['matchPoints'];
            
            if (array_key_exists(strval($player['id']), $this->seasonPoints)) {
                $this->seasonPoints[strval($player['id'])] += $points;
            } else {
                $this->seasonPoints[strval($player['id'])] = $points;
            }
            if (array_key_exists(strval($player['id']), $this->sessionPoints)) {
                $this->sessionPoints[strval($player['id'])] += $points;
            } else {
                $this->sessionPoints[strval($player['id'])] = $points;
            }
            $singlesMatchPlayEventViewBean->players[$playerIndex]['seasonPoints'] = $this->seasonPoints[strval($player['id'])];
            $singlesMatchPlayEventViewBean->players[$playerIndex]['sessionPoints'] = $this->sessionPoints[strval($player['id'])];
        }
    }
}
