<?php
namespace App\View;

class SinglesStrokePlaySeasonStandingsViewBean {
    private array $seasonPoints;
    private array $sessionPoints;

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
    public function setSeasonPoints( array $seasonPoints): void {
        $this->seasonPoints = $seasonPoints;
    }

    /**
     * @param array $sessionPoints : $sessionPoints
     */
    public function setSessionPoints( array $sessionPoints): void {
        $this->sessionPoints = $sessionPoints;
    }

    public function __construct() {
        $this->seasonPoints = [];
        $this->sessionPoints = [];
    }

    public function updatePlayerPoints(SinglesStrokePlayEventViewBean &$singlesStrokePlayEventViewBean): void {
        for ($playerIndex = 0; $playerIndex < sizeof($singlesStrokePlayEventViewBean->players); $playerIndex++) {
            $player = $singlesStrokePlayEventViewBean->players[$playerIndex];
            
            if ($player['place'] <= 10) {
                $points = (11 - $player['place']) * 20;
                
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
            }
            if (array_key_exists(strval($player['id']), $this->seasonPoints)) {
                $singlesStrokePlayEventViewBean->players[$playerIndex]['seasonPoints'] = $this->seasonPoints[strval($player['id'])];
            }
            if (array_key_exists(strval($player['id']), $this->sessionPoints)) {
                $singlesStrokePlayEventViewBean->players[$playerIndex]['sessionPoints'] = $this->sessionPoints[strval($player['id'])];
            }
        }
    }
}
