<?php
namespace App\View;

use App\Entity\GameDE;
use App\Entity\EventDE;
use App\Entity\NineDE;
use App\Entity\ScoreDE;
use App\Entity\PlayerDE;
use App\Entity\TeeDE;
use App\Model\EventFormatType;
use App\Model\EventType;

class SinglesStrokePlayEventViewBean {
    public array $course;
    public string $description;
    public bool $displayNet;
    public bool $displayTotal;
    public int $format;
    public int $ninesPlayed;
    public array $players;
    public int $type;
    public bool $withHandicapping;
    
    
    public function __construct(EventDE $event) {
        $this->type = $event->getEventtype();
        $this->format = $event->getFormat();
        $this->ninesPlayed = empty($event->getSecondNine()) ? 1 : 2; 
        
        $this->initializeEvent($event);
        $this->initializeCourse($event);
        $this->initializePlayers($event);
    }
    
    /**
     * Sort array based on a specified array property
     *     Example: array_sort($people, 'age', SORT_DESC);
     *     
     * @param array $array array to sort
     * @param string $on property to sort on
     * @param int|string $order sort order, ascending or decending
     *
     * @return array sorted array
     * 
     */
    function array_sort(array $array, string $on, int|string $order=SORT_ASC): array {
        $new_array = array();
        $sortable_array = array();
        
        if (count($array) > 0) {
            foreach ($array as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        if ($k2 == $on) {
                            $sortable_array[$k] = $v2;
                        }
                    }
                } else {
                    $sortable_array[$k] = $v;
                }
            }
            switch ($order) {
                case SORT_ASC:
                    asort($sortable_array);
                    break;
                case SORT_DESC:
                    arsort($sortable_array);
                    break;
            }
            foreach ($sortable_array as $k => $v) {
                $new_array[$k] = $array[$k];
            }
        }
        return $new_array;
    }
    
    public function calculateEventResults(EventDE $event): void {
        for ($nine = 0; $nine < $this->ninesPlayed; $nine++) {
            $nineName = $this->course['nineNames'][$nine];
            
            for ($holeNumber = 0; $holeNumber < 9; $holeNumber++) {
                $lowestScore = [];
                $lowestScore[strval($holeNumber)] = 255;
                
                for ($playerIndex = 0; $playerIndex < sizeof($this->players); $playerIndex++) {
                    $player = $this->players[$playerIndex];
                    $playerHoleScore = $nine == 0 ? $player['score'][$holeNumber] : $player['score'][$holeNumber + 9];
                    
                    if ($playerHoleScore < $lowestScore[strval($holeNumber)]) {
                        $lowestScore[strval($holeNumber)] = $playerHoleScore;
                        $lowestScore['count'] = 1;
                        $lowestScore['playerIndex'] = $playerIndex;
                    } else if ($playerHoleScore == $lowestScore[strval($holeNumber)]) {
                        $lowestScore['count']++;
                    }
                }
                if ($lowestScore['count'] == 1) {
                    $playerIndex = $lowestScore['playerIndex'];
                    
                    if ($this->players[$playerIndex]['skins'] == null) {
                        $this->players[$playerIndex]['skins'] = $nineName . " #" . strval($holeNumber + 1);
                    } else {
                        $this->players[$playerIndex]['skins'] .= ', ' . $nineName . " #" . strval($holeNumber + 1);
                    }
                }
            }
        }
        $this->sortEventResults($event);
    }

	/**
	 * @param array $a1
	 * @param array|null $a2
	 *
	 * @return int[]
	 */
    private function combine(array $a1, ?array $a2): array {
        $a = [];
        for ($i = 0; $i < sizeof($a1); $i++) {
            $a[] = $a1[$i];
        }
        if (!empty($a2)) {
            for ($i = 0; $i < sizeof($a2); $i++) {
                $a[] = $a2[$i];
            }
        }
        return $a;
    }
    
    /**
     * @param array $firstNineHoleHandicaps
     * @param array $secondNineHoleHandicaps
     *
     * @return number[]|array[]
     */
    private function eighteenHoleHandicaps(array &$firstNineHoleHandicaps, array &$secondNineHoleHandicaps): array {
        ksort($firstNineHoleHandicaps);
        
        if (!empty($secondNineHoleHandicaps)) {
            ksort($secondNineHoleHandicaps);
        }
        
        $holeHandicaps = [];
        for ($holeHandicap = 1; $holeHandicap <= 18; $holeHandicap++) {
            $key = '' . $holeHandicap;
            
            if (array_key_exists($key, $firstNineHoleHandicaps)) {
                $holeHandicaps[$key][] = $firstNineHoleHandicaps[$key];
            } else {
                $holeHandicaps[$key][] = 0;
            }
            if (empty($secondNineHoleHandicaps)) {
                $holeHandicaps[$key][] = 0;
            } else {
                if (array_key_exists($key, $secondNineHoleHandicaps)) {
                    $holeHandicaps[$key][] = $secondNineHoleHandicaps[$key];
                } else {
                    $holeHandicaps[$key][] = 0;
                }
            }
        }
        return $holeHandicaps;
    }
    
    public function format_player_points(float $points): string {
        return number_format($points, 1);
    }
    
    /**
     * @param NineDE $nine
     * @param string $teeName
     *
     * @return array|NULL[]
     */
    private function holeHandicaps(NineDE $nine, string $teeName): array {
        if ($nine == null) {
            return [];
        }
        $holeHandicaps = [];
        
        $tee = $this->tee($nine, $teeName); 
        foreach($tee->getHoles() as $hole) {
            $holeHandicaps[ '' . $hole->getHandicap() ] = $hole->getHoleNumber();
        }
        return $holeHandicaps;
    }
    
    /**
     * @param int $size
     * @param string $value
     *
     * @return array of length $size filled with $value strings
     */
    private function fill(int $size, string $value): array {
        $a = [];
        for ($i = 0; $i < $size; $i++) {
            $a[] = $value;
        }
        return $a;
    }

	/**
	 * @param array $score
	 *
	 * @return int[]
	 */
    private function firstNineScore(array $score): array {
        $a = [];
        for ($i = 0; $i < 9; $i++) {
            $a[] = $score[$i];
        }
        return $a;
    }
    
    private function initializeCourse(EventDE $event): void {
        $firstNineHoleHandicaps = $this->holeHandicaps($event->getNine(), $event->getTee()->getName());
        $secondNineHoleHandicaps = empty($event->getSecondnine()) ? null : $this->holeHandicaps($event->getSecondNine(), $event->getTee()->getName());
        $eighteenHoleHandicaps = $this->eighteenHoleHandicaps($firstNineHoleHandicaps, $secondNineHoleHandicaps);
        
        $firstNineName = $event->getNine()->getName();
        $secondNineName = empty($event->getSecondnine()) ? null : $event->getSecondnine()->getName();
        
        $course = [
            'name' => $event->getCourse()->getName(),
            'teeName' => $event->getTee()->getName(),
            'firstNineName' => $firstNineName,
            'firstNineHoleHandicaps' => $firstNineHoleHandicaps,
            'secondNineName' => $secondNineName,
            'secondNineHoleHandicaps' => $secondNineHoleHandicaps,
            'nineNames' => [$firstNineName, $secondNineName],
            'eighteenHoleHandicaps' => $eighteenHoleHandicaps
        ];
        
        $this->course = $course;
    }
    
    private function initializeEvent(EventDE $event): void {
        $this->withHandicapping = $event->isWithhandicapping();
        $this->description = EventType::toString($this->type) . ': ' . EventFormatType::toString($this->format);
        
        if ($this->withHandicapping) {
            $this->description .= ' WITH HANDICAPPING';
            
            $this->displayNet = true;
            $this->displayTotal = true;
        } else {
            $this-> description .= ' WITH NO HANDICAPPING';
        
            $this->displayNet = false;
            $this->displayTotal = true;
        }
    }
    
    private function initializePlayer(PlayerDE $p, EventDE $event, GameDE $game): array {
        $playerScores = $game->getSinglePlayerScores($p);
        
        if ($this->ninesPlayed == 1) {
            $firstNineScore = $playerScores[0];
            
            $firstNineTee = $firstNineScore->getTee();
            $slope = $firstNineTee->getSlope(); 
            $par = $firstNineTee->getPar() * 2;
            $rating = $firstNineTee->getRating() * 2;
            
            $secondNineScore = null;
            $secondNineHandicap = null;
        } else {
            if ($playerScores[0]->getTee()->getNine()->getId() == $event->getNine()->getId()) {
                $firstNineScore = $playerScores[0];
                $secondNineScore = $playerScores[1];
            } else {
                $firstNineScore = $playerScores[1];
                $secondNineScore = $playerScores[0];
            }
            $firstNineTee = $firstNineScore->getTee();
            $secondNineTee = $secondNineScore->getTee();
            
            $slope = intval(($firstNineTee->getSlope() + $secondNineTee->getSlope()) /2);
            $par = $firstNineTee->getPar() + $secondNineTee->getPar();
            $rating = $firstNineTee->getRating() + $secondNineTee->getRating();
        }
        $firstNineHandicap = $firstNineScore->getHandicap();
        $handicapIndex = $firstNineScore->getCurrenthandicapindex() * 2;
        $eighteenHoleHandicap = round((($handicapIndex * $slope) / 113) + ($rating - $par), 0, PHP_ROUND_HALF_UP);
        
        if ($this->ninesPlayed == 2) {
            if ($eighteenHoleHandicap & 1) {
                $secondNineHandicap = $eighteenHoleHandicap - $firstNineHandicap;
            } else {
                $secondNineHandicap = $eighteenHoleHandicap / 2;
            }
            $secondNineAdjustedScores = ScoreDE::unpack($secondNineScore->getAdjustedstrokes());
            $secondNineNetScores = $secondNineScore->getNetstrokes($secondNineAdjustedScores, $secondNineHandicap);
        }
        $firstNineAdjustedScores = ScoreDE::unpack($firstNineScore->getAdjustedstrokes());
        $firstNineNetScores = $firstNineScore->getNetstrokes($firstNineAdjustedScores, $firstNineHandicap);
        
        $score = $this->combine($firstNineAdjustedScores, $secondNineScore == null ? null : $secondNineAdjustedScores);
        $netScore = $this->combine($firstNineNetScores, $secondNineScore == null ? null : $secondNineNetScores);
        
        $player = [
            'id' => $p->getId(),
            'name' => $p->getName()->getFullname(),
            'eighteenHoleHandicap' => $eighteenHoleHandicap,
            'score' => $score,
            'totalScore' => array_sum($score),
            'netScore' => $netScore,
            'totalNetScore' => array_sum($netScore),
            'firstNineHandicap' => $firstNineHandicap,
            'firstNineScores' => $firstNineAdjustedScores,
            'firstNineTotalScore' => array_sum($firstNineAdjustedScores),
            'firstNineNetScores' => $firstNineNetScores,
            'firstNineTotalNetScore' => array_sum($firstNineNetScores),
            'secondNineHandicap' => $secondNineHandicap,
            'secondNineScores' => $this->ninesPlayed == 1 ? null : $secondNineAdjustedScores,
            'secondNineTotalScore' => $this->ninesPlayed == 1 ? 0 : array_sum($secondNineAdjustedScores),
            'secondNineNetScores' => $this->ninesPlayed == 1 ? null : $secondNineNetScores,
            'secondNineTotalNetScore' => $this->ninesPlayed == 1 ? 0 : array_sum($secondNineNetScores),
                        
            'place' => null,
            'seasonPoints' => null,
            'sessionPoints' => null,
            'skins' => null,
            'tieBreaker' => null
        ];
        
        return $player;
    }
    
    private function initializePlayers(EventDE $event): void {
        $players = [];
        
        foreach($event->getGames() as $game) {
            foreach($game->getPlayers() as $player) {
                $players[] = $this->initializePlayer($player, $event, $game);
            }
        }
        $this->players = $players;
    }

    /**
     * @param EventDE $event
     */
    private function sortEventResultsOnNetScores(EventDE $event): void {
        $handicaps = $this->course['eighteenHoleHandicaps'];
        
        for ($i = 0; $i < sizeof($this->players); $i++) {
            for ($j = $i + 1; $j < sizeof($this->players); $j++) {
                $swap = false;
                
                if ($this->players[$j]['totalNetScore'] < $this->players[$i]['totalNetScore']) {
                    $swap = true;
                } else {
                    if ($this->players[$j]['totalNetScore'] == $this->players[$i]['totalNetScore']) {
                        for ($holeHandicap = 1; $holeHandicap <= 18; $holeHandicap++) {
                            $key = '' . $holeHandicap . '';
                            $holeHandicaps = $handicaps[$key];
                            
                            for ($holdHandicapIndex = 0; $holdHandicapIndex < 2; $holdHandicapIndex++) {
                                $hole = $holeHandicaps[$holdHandicapIndex] - 1; 
                                
                                if ($hole > 0) {
                                    if ($this->players[$j]['netScore'][$hole] < $this->players[$i]['netScore'][$hole]) {
                                        $swap = true;
                                        $this->players[$i]['tieBreaker'] = null;
                                        
                                        $nineName = $holdHandicapIndex == 0 ? $this->course['firstNineName'] : $this->course['secondNineName'];
                                        $this->players[$j]['tieBreaker'] = $nineName . ' #' . $hole;
                                        break;
                                    }
                                }
                                If ($swap) {
                                    break;
                                }
                            }
                        }
                    }
                }
                if ($swap) {
                    $player = $this->players[$i];
                    $this->players[$i] = $this->players[$j];
                    $this->players[$j] = $player;
                }
            }
        }
        $place = 1;
        for ($i = 0; $i < sizeof($this->players); $i++) {
            for ($j = $i + 1; $j <= sizeof($this->players); $j++) {
                if ($j == sizeof($this->players) || $this->players[$i]['tieBreaker'] != null) {
                    $this->players[$i]['place'] = $place;
                    break;
                } else if ($this->players[$j]['totalNetScore'] == $this->players[$i]['totalNetScore']) {
                    $this->players[$i]['place'] = $place;
                    $this->players[$j]['place'] = $place;
                } else {
                    $this->players[$i]['place'] = $place;
                    break;
                }
            }
            $place = $i + 2;
        }
    }
    
    /**
     * @param EventDE $event
     */
    private function sortEventResultsOnTotalScores(EventDE $event): void {
        $handicaps = $this->course['eighteenHoleHandicaps'];
        
        for ($i = 0; $i < sizeof($this->players); $i++) {
            for ($j = $i + 1; $j < sizeof($this->players); $j++) {
                $swap = false;
                
                if ($this->players[$j]['totalScore'] < $this->players[$i]['totalScore']) {
                    $swap = true;
                } else {
                    if ($this->players[$j]['totalScore'] == $this->players[$i]['totalScore']) {
                        for ($holeHandicap = 1; $holeHandicap <= 18; $holeHandicap++) {
                            $key = '' . $holeHandicap;
                            $holeHandicaps = $handicaps[$key];
                            
                            for ($holdHandicapIndex = 0; $holdHandicapIndex < 2; $holdHandicapIndex++) {
                                $hole = $holeHandicaps[$holdHandicapIndex] - 1;
                                
                                if ($hole > 0) {
                                    if ($this->players[$j]['score'][$hole] < $this->players[$i]['score'][$hole]) {
                                        $swap = true;
                                        $this->players[$i]['tieBreaker'] = null;
                                        
                                        $nineName = $holdHandicapIndex == 0 ? $this->course['firstNineName'] : $this->course['secondNineName'];
                                        $this->players[$j]['tieBreaker'] = $nineName . ' #' . $hole;
                                        break;
                                    }
                                }
                                If ($swap) {
                                    break;
                                }
                            }
                        }
                    }
                }
                if ($swap) {
                    $player = $this->players[$i];
                    $this->players[$i] = $this->players[$j];
                    $this->players[$j] = $player;
                }
            }
        }
        $place = 1;
        for ($i = 0; $i < sizeof($this->players); $i++) {
            for ($j = $i + 1; $j <= sizeof($this->players); $j++) {
                if ($j == sizeof($this->players) || $this->players[$i]['tieBreaker'] != null) {
                    $this->players[$i]['place'] = $place;
                    break;
                } else if ($this->players[$j]['totalScore'] == $this->players[$i]['totalScore']) {
                    $this->players[$i]['place'] = $place;
                    $this->players[$j]['place'] = $place;
                } else {
                    $this->players[$i]['place'] = $place;
                    break;
                }
            }
            $place = $i + 2;
        }
    }
    
    /**
     * @param EventDE $event
     */
    private function sortEventResults(EventDE $event): void {
        if ($this->withHandicapping) {
            $this->sortEventResultsOnNetScores($event);
        } else {
            $this->sortEventResultsOnTotalScores($event);
        }
    }

	/**
	 * @param array $score
	 *
	 * @return int[]
	 */
    private function secondNineScore(array $score): array {
        if ($this->ninesPlayed == 1) {
            return [];
        }
        $a = [];
        for ($i = 0; $i < 9; $i++) {
            $a[] = $score[$i + 9];
        }
        return $a;
    }

	/**
	 * @param NineDE $nine
	 * @param string $teeName
	 *
	 * @return TeeDE|null
	 */
    private function tee(NineDE $nine, string $teeName): ?TeeDE {
        foreach($nine->getTees() as $tee) {
            if ($tee->getName() == $teeName) { 
                return $tee;
            }
        }
		return null;
    }
    
    /**
     * @param string $score of packed integers $score
     * @return array|number[] of expanded integers
     */
    private function unpackedScore(string $score): array {
        if (empty($score)) {
            return [];
        }
        $a = ScoreDE::unpack($score);
        return $a;
    }
}