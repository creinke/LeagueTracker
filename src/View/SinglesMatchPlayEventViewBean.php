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

class SinglesMatchPlayEventViewBean {
    public array $course;
    public string $description;
    public bool $displayNet;
    public bool $displayTotal;
    public ?int $format;
    public array $gamePlayerMatchResults;
    public int $ninesPlayed;
    public array $players;
    public ?int $type;
    public bool $withHandicapping;
    
    public function __construct(EventDE $event) {
        $this->type = $event->getEventtype();
        $this->format = $event->getFormat();
        $this->ninesPlayed = empty($event->getSecondNine()) ? 1 : 2; 
        
        $this->initializeEvent($event);
        $this->initializeCourse($event);
        $this->initializePlayerMatches($event);
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
    public function array_sort(array $array, string $on, int|string $order=SORT_ASC): array {
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
    
    /**
     * @return int The adjusted net strokes for the hole specified.
     */
    private function calculateAdjustedNetStrokes(int $holeStrokes, int $holeHandicap, int $handicap) : int {
        $x = intval(abs($handicap / 9));
        $y = intval(($handicap % 9) * 2 >= $holeHandicap ? 1 : 0);
        $strokes = intval($x + $y);
        return $holeStrokes - $strokes;
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
            $key = '' . $holeHandicap . '';
            
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
            $holeHandicaps['' . $hole->getHandicap() . ''] = $hole->getHoleNumber();
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
    
    public function format_player_points($points): string {
        return number_format($points, 1);    
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
    
    private function initializePlayer(PlayerDE $p, array $playerScores, EventDE $event, GameDE $game): array {
        if ($this->ninesPlayed == 1) {
            $firstNineScore = $playerScores[0];

            $firstNineTee = $firstNineScore->getTee();
            $slope = $firstNineTee->getSlope(); 
            $par = $firstNineTee->getPar() * 2;
            $rating = $firstNineTee->getRating() * 2;
            
            $secondNineScore = null;
            $secondNineHandicap = null;
            $secondNineTee = null;
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
        $eighteenHoleHandicap = round((($handicapIndex * $slope) / 113) + ($rating - $par));
        
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
        
        $firstNine = [
            'tee' => $firstNineTee, 
            'handicap' => $firstNineHandicap,
            'scores' => ScoreDE::unpack($firstNineScore->getStrokes()),
            'totalScore' => array_sum(ScoreDE::unpack($firstNineScore->getStrokes())),
	        'adjustedTotalScore' => $firstNineScore->getAdjustedstrokesTotal(),
            'netScores' => $firstNineNetScores,
            'totalNetScore' => array_sum($firstNineNetScores)
        ];
        
        $secondNine = [
            'tee' => $secondNineTee,
            'handicap' => $secondNineHandicap,
            'scores' => $this->ninesPlayed == 1 ? null : ScoreDE::unpack($secondNineScore->getStrokes()),
            'totalScore' => $this->ninesPlayed == 1 ? 0 : array_sum(ScoreDE::unpack($secondNineScore->getStrokes())),
            'adjustedTotalScore' => $this->ninesPlayed == 1 ? 0 : $secondNineScore->getAdjustedstrokesTotal(),
            'netScores' => $this->ninesPlayed == 1 ? null : $secondNineNetScores,
            'totalNetScore' => $this->ninesPlayed == 1 ? 0 : array_sum($secondNineNetScores)
        ];
        
        $player = [
            'id' => $p->getId(),
            'name' => $p->getName()->getFullname(),
            'eighteenHoleHandicap' => $eighteenHoleHandicap,
            'score' => $score,
            'totalScore' => array_sum($score),
            'netScore' => $netScore,
            'totalNetScore' => array_sum($netScore),
            'firstNine' => $firstNine,
            'secondNine' => $secondNine, 
                        
            'matchPoints' => 0,
            'place' => null,
            'seasonPoints' => null,
            'sessionPoints' => null,
            'skins' => null,
            'tieBreaker' => null
        ];
        
        return $player;
    }
    
    private function initializePlayerMatches(EventDE $event): void {
        $gameMatches = [];
        $this->players = [];
        
        foreach($event->getGames() as $game) {
            $playerMatches = [];
            
            foreach($game->getPlayermatches() as $playerMatch) {
                $match = [];
                $this->players[] = $this->initializePlayer($playerMatch->getPlayerone(), $playerMatch->getPlayerOneScores()->toArray(), $event, $game);
                $match['playerOne'] = &$this->players[sizeof($this->players) - 1];
                
                $this->players[] = $this->initializePlayer($playerMatch->getPlayertwo(), $playerMatch->getPlayerTwoScores()->toArray(), $event, $game);
                $match['playerTwo'] = &$this->players[sizeof($this->players) - 1];
                
                $playerMatches[] = $match;
            }
            $gameMatches[] = $playerMatches;
        }
        $gamePlayerMatchResults = [];
        
        foreach($gameMatches as $gameMatch) {
            $playerMatchResults = [];
            
            foreach($gameMatch as $playerMatch) {
                $playerOneMatchResults = [];
                $playerTwoMatchResults = [];
                $playerOneTotalNetScore = $playerTwoTotalNetScore = 0;
                
                $playerOne = &$playerMatch['playerOne'];
                $playerOneName = $playerOne['name'];
                $playerTwo = &$playerMatch['playerTwo'];
                $playerTwoName = $playerTwo['name'];
                
                for ($nineIndex = 0; $nineIndex < $this->ninesPlayed; $nineIndex++) {
                    $nineKey = $nineIndex == 0 ? 'firstNine' : 'secondNine';
                    $nine = $playerOne[$nineKey]['tee']->getNine();
                    $holeHandicaps = $nineIndex == 0 ? $this->course['firstNineHoleHandicaps'] : $this->course['secondNineHoleHandicaps'];
                    
                    $playerOneHoleStrokes = $playerOne[$nineKey]['scores'];
	                $playerOneHoleStrokesTotal = $playerOne[$nineKey]['totalScore'];
	                $playerOneAdjustedHoleStrokesTotal = $playerOne[$nineKey]['adjustedTotalScore'];
                    $playerOneHolePoints = [];
                    $playerOneNetPoints = 0;
                    $playerOneTotalHolePoints = 0;
                    $playerOneTotalPoints = 0;
                    $playerOneAdjustedNetStrokes = [];
                    
                    $playerTwoHoleStrokes = $playerTwo[$nineKey]['scores'];
                    $playerTwoHoleStrokesTotal = $playerTwo[$nineKey]['totalScore'];
	                $playerTwoAdjustedHoleStrokesTotal = $playerTwo[$nineKey]['adjustedTotalScore'];
                    $playerTwoHolePoints = [];
                    $playerTwoNetPoints = 0;
                    $playerTwoTotalHolePoints = 0;
                    $playerTwoTotalPoints = 0;
                    $playerTwoAdjustedNetStrokes = [];
                    
                    // $playerOneNetStrokes = $playerOne[$nineKey]['netScores'];
                    // $playerTwoNetStrokes = $playerTwo[$nineKey]['netScores'];
                    $playerOneHandicap = $playerOne[$nineKey]['handicap'];
                    $playerTwoHandicap = $playerTwo[$nineKey]['handicap'];
                    $strokeDifference = $playerTwoHandicap > $playerOneHandicap ? $playerOneHandicap : $playerTwoHandicap;
                    $playerOneAdjustedHandicap = $playerOneHandicap - $strokeDifference;
                    $playerTwoAdjustedHandicap = $playerTwoHandicap - $strokeDifference;
                    
                    for ($holenumber = 0; $holenumber < 9; $holenumber++) {
                        $holeHandicap = intval(array_search($holenumber + 1, $holeHandicaps));
                        
                        if ($playerOneHoleStrokes[$holenumber] == 0 || $playerTwoHoleStrokes[$holenumber] == 0) {
                            $playerOneAdjustedNetStrokes[] = $playerOneHoleStrokes[$holenumber];
                            $playerTwoAdjustedNetStrokes[] = $playerTwoHoleStrokes[$holenumber];
                            
                            $playerOneHolePoints[$holenumber] = (float) 0.5;
                            $playerOneTotalPoints += 0.5;
                            $playerOneTotalHolePoints += 0.5;
                            
                            $playerTwoHolePoints[$holenumber] = (float) 0.5;
                            $playerTwoTotalPoints += 0.5;
                            $playerTwoTotalHolePoints += 0.5;
                        } else {
                            $playerOneAdjustedNetHoleStrokes = $this->calculateAdjustedNetStrokes($playerOneHoleStrokes[$holenumber], $holeHandicap, $playerOneAdjustedHandicap);
                            $playerOneAdjustedNetStrokes[] = $playerOneAdjustedNetHoleStrokes;
                            $playerTwoAdjustedNetHoleStrokes = $this->calculateAdjustedNetStrokes($playerTwoHoleStrokes[$holenumber], $holeHandicap, $playerTwoAdjustedHandicap);
                            $playerTwoAdjustedNetStrokes[] = $playerTwoAdjustedNetHoleStrokes;
                            
                            if ($playerOneAdjustedNetHoleStrokes < $playerTwoAdjustedNetHoleStrokes) {
                                $playerOneHolePoints[$holenumber] = 1;
                                $playerTwoHolePoints[$holenumber] = 0;
                                $playerOneTotalPoints += 1;
                                $playerOneTotalHolePoints += 1;
                            } else if ($playerOneAdjustedNetHoleStrokes > $playerTwoAdjustedNetHoleStrokes) {
                                $playerOneHolePoints[$holenumber] = 0;
                                $playerTwoHolePoints[$holenumber] = 1;
                                $playerTwoTotalPoints += 1;
                                $playerTwoTotalHolePoints += 1;
                            } else {
                                $playerOneHolePoints[$holenumber] = (float) 0.5;
                                $playerOneTotalPoints += 0.5;
                                $playerOneTotalHolePoints += 0.5;
                                
                                $playerTwoHolePoints[$holenumber] = (float) 0.5;
                                $playerTwoTotalPoints += 0.5;
                                $playerTwoTotalHolePoints += 0.5;
                            }
                        }
                    }
                    $playerOneNineNetScore = $playerOne[$nineKey]['totalNetScore'];
                    $playerOneTotalNetScore += $playerOneNineNetScore;
                    $playerTwoNineNetScore = $playerTwo[$nineKey]['totalNetScore'];
                    $playerTwoTotalNetScore += $playerTwoNineNetScore;
                    
                    if ($nineIndex + 1 == $this->ninesPlayed) {
                        if ($playerOneTotalNetScore < $playerTwoTotalNetScore) {
                            $playerOneTotalPoints += 1;
                            $playerOneNetPoints = 1;
                        } else if ($playerOneTotalNetScore > $playerTwoTotalNetScore) {
                            $playerTwoTotalPoints += 1;
                            $playerTwoNetPoints = 1;
                        } else {
                            $playerOneTotalPoints += .5;
                            $playerOneNetPoints += .5;
                            $playerTwoTotalPoints += .5;
                            $playerTwoNetPoints += .5;
                        }
                    }
                    $playerOne['matchPoints'] += $playerOneTotalPoints;

                    $playerOneResultsViewBean = new PlayerResultsViewBean(
                        $nine, $playerOneName, $playerOneHoleStrokes, $playerOneHoleStrokesTotal, $playerOneAdjustedHoleStrokesTotal, $playerOneHandicap, $playerOneNineNetScore,
                        $playerOneAdjustedNetStrokes, $playerOneHolePoints, $playerOneTotalHolePoints, $playerOneNetPoints, $playerOneTotalPoints);
                    
                    $playerTwo['matchPoints'] += $playerTwoTotalPoints;
                    $playerTwoResultsViewBean = new PlayerResultsViewBean(
                        $nine, $playerTwoName, $playerTwoHoleStrokes, $playerTwoHoleStrokesTotal, $playerTwoAdjustedHoleStrokesTotal, $playerTwoHandicap, $playerTwoNineNetScore,
                        $playerTwoAdjustedNetStrokes, $playerTwoHolePoints, $playerTwoTotalHolePoints, $playerTwoNetPoints, $playerTwoTotalPoints);
                    
                    $playerOneMatchResults[] = $playerOneResultsViewBean;
                    $playerTwoMatchResults[] = $playerTwoResultsViewBean;
                }
                $playerMatchResults[] = [$playerOneMatchResults, $playerTwoMatchResults];
            }
            $gamePlayerMatchResults[] = $playerMatchResults;
        }
        $this->gamePlayerMatchResults = $gamePlayerMatchResults;
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
                                
                                if ($hole >= 0) {
                                    if ($this->players[$j]['netScore'][$hole] < $this->players[$i]['netScore'][$hole]) {
                                        $swap = true;
                                        $this->players[$i]['tieBreaker'] = null;
                                        
                                        $nineName = $holdHandicapIndex == 0 ? $this->course['firstNineName'] : $this->course['secondNineName'];
                                        $this->players[$j]['tieBreaker'] = $nineName . ' #' . ($hole + 1);
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
                            $key = '' . $holeHandicap . '';
                            $holeHandicaps = $handicaps[$key];
                            
                            for ($holdHandicapIndex = 0; $holdHandicapIndex < 2; $holdHandicapIndex++) {
                                $hole = $holeHandicaps[$holdHandicapIndex] - 1;
                                
                                if ($hole >= 0) {
                                    if ($this->players[$j]['score'][$hole] < $this->players[$i]['score'][$hole]) {
                                        $swap = true;
                                        $this->players[$i]['tieBreaker'] = null;
                                        
                                        $nineName = $holdHandicapIndex == 0 ? $this->course['firstNineName'] : $this->course['secondNineName'];
                                        $this->players[$j]['tieBreaker'] = $nineName . ' #' . ($hole + 1);
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
    private function tee(NineDE $nine, string $teeName) : ?TeeDE {
        foreach($nine->getTees() as $tee) {
            if ($tee->getName() == $teeName) { 
                return $tee;
            }
        }
		return null;
    }
    
    /**
     * @param string $score of packed integers $score
     *
     * @return array|number[] of expanded integers
     */
    private function unpackedScore( string $score): array {
        if (empty($score)) {
            return [];
        }
        $a = ScoreDE::unpack($score);
        return $a;
    }
}