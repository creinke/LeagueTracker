<?php
namespace App\View;

use App\Entity\EventDE;
use App\Entity\NineDE;
use App\Entity\ScoreDE;
use App\Entity\TeamgameplayerDE;
use App\Entity\TeeDE;
use Doctrine\ORM\EntityManagerInterface;
use App\Model\EventFormatType;
use App\Repository\ScoreRepository;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;

class TeamEventViewBean {
    public array $course;
    public bool $displayNet;
	public bool $displayTotal;
	public string $description;
	private EntityManagerInterface $em;
    public string $format;
    public bool $isLowTeamNet;
    public bool $isScramble;
    public bool $isShamble;
	private LoggerInterface $logger;
    public int $ninesPlayed;
    public array $teams;
    public bool $withHandicapping;
    public bool $withHandicapPercent;

	/**
	 * @throws Exception
	 */
	public function __construct(EventDE $event, EntityManagerInterface $em, LoggerInterface $logger) {
        $this->format = $event->getFormat();
        $this->em = $em;
		$this->logger = $logger;
        $this->ninesPlayed = empty($event->getSecondNine()) ? 1 : 2; 
        
        $this->initializeEvent($event);
        $this->initializeCourse($event);
        $this->initializeTeams($event);
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
        foreach($this->teams as &$team) {
            if ($event->isScramble($event->getFormat())) {
                $totalScore = $team['firstNineTotalScore'] + $team['secondNineTotalScore'];
                $team['totalScore'] = $team['totalTeamScore'] = $totalScore;
                $team['firstNineTotalTeamScore'] = $team['firstNineTotalScore'];
                $team['secondNineTotalTeamScore'] = $team['secondNineTotalScore'];
                
                if ($this->withHandicapping) {
                    $players = $this->array_sort($team['players'], 'handicapIndex', SORT_ASC);
                    
                    for ($playerIndex = 0; $playerIndex < sizeof($players); $playerIndex++) {
                        $player = $players[$playerIndex];
                        
                        if (sizeof($players) == 2) {
                            if ($playerIndex == 0) {
                                $adjustedHandicapIndex = $player['handicapIndex'] * .35;
                            } else if ($playerIndex == 1) {
                                $adjustedHandicapIndex = $player['handicapIndex'] * .15;
                            } 
                        } else {
                            if ($playerIndex == 0) {
                                $adjustedHandicapIndex = $player['handicapIndex'] * .20;
                            } else if ($playerIndex == 1) {
                                $adjustedHandicapIndex = $player['handicapIndex'] * .15;
                            } else if ($playerIndex == 2) {
                                $adjustedHandicapIndex = $player['handicapIndex'] * .10;
                            } else {
                                $adjustedHandicapIndex = $player['handicapIndex'] * .05;
                            }
                        }
                        $players[$playerIndex]['adjustedHandicapIndex'] = $adjustedHandicapIndex;
                    }
                    $teamAdjustedHandicapIndex = 0;
                    foreach($players as $player) {
                        $teamAdjustedHandicapIndex += $player['adjustedHandicapIndex'];
                    }
                    $tee = $event->getTee();
                    $slope = $tee->getSlope();
                    $rating = $tee->getRating();
                    $par = $tee->getPar();
                    $teamHandicapIndex = (float) $teamAdjustedHandicapIndex * ($slope / 113) + ($rating - $par);
	                $team['handicap'] = (int) round($teamHandicapIndex * 2, 0, PHP_ROUND_HALF_UP);

                    $score = $team['score'];
                    $firstNineNetScores = $this->teamNetScore($team['firstNineScores'], $event->getNine(), $event->getTee()->getName(), $teamHandicapIndex);
                    $firstNineTotalNetScore = $firstNineTotalTeamNetScore = array_sum($firstNineNetScores);
                    $secondNineNetScores = empty($event->getSecondnine()) ? null : $this->teamNetScore($team['secondNineScores'], $event->getSecondnine(), $event->getTee()->getName(), $teamHandicapIndex);
                    $secondNineTotalNetScore = $secondNineTotalTeamNetScore = empty($event->getSecondnine()) ? null : array_sum($secondNineNetScores);
                    
                    $netScore = $this->combine($firstNineNetScores, $secondNineNetScores);
                    $totalNetScore = $totalTeamNetScore = array_sum($netScore);

                    $team['netScore'] = $netScore;
                    $team['totalNetScore'] = $totalNetScore;
                    $team['totalTeamNetScore'] = $totalTeamNetScore;
                    
                    $team['firstNineNetScores'] = $firstNineNetScores;
                    $team['firstNineTotalNetScore'] = $firstNineTotalNetScore;
                    $team['firstNineTotalTeamNetScore'] = $firstNineTotalTeamNetScore;
                    
                    $team['secondNineNetScores'] = $secondNineNetScores;
                    $team['secondNineTotalNetScore'] = $secondNineTotalNetScore;
                    $team['secondNineTotalTeamNetScore'] = $secondNineTotalTeamNetScore;
                } else {
                    $team['netScore'] = $team['score'];
                    $team['totalNetScore'] = $team['totalScore'];
                    $team['totalTeamNetScore'] = $team['totalScore'];
                
                    $team['firstNineNetScores'] = $team['firstNineScores'];
                    $team['firstNineTotalNetScore'] = $team['firstNineTotalScore'];
                    $team['firstNineTotalTeamNetScore'] = $team['firstNineTotalScore'];

                    $team['secondNineNetScores'] = $team['secondNineScores'];
                    $team['secondNineTotalNetScore'] = $team['secondNineTotalScore'];
                    $team['secondNineTotalTeamNetScore'] = $team['secondNineTotalScore'];
                }
            } else { 
                $score = [];
                $netScore = [];
                $firstNineScores = $this->fill(9, 15);
                $firstNineNetScores = $this->fill(9, 15);
                $secondNineScores = $this->fill(9, 15);
                $secondNineNetScores = $this->fill(9, 15);
                
                // Determine the lowest score and the lowest net score for each hole for all players
                foreach($team['players'] as $player) {
                    for ($holeNumber = 0; $holeNumber < 9; $holeNumber++) {
                        if ($player['score'][$holeNumber] < $firstNineScores[$holeNumber]) {
                            $score[$holeNumber] = $player['score'][$holeNumber];
                            $firstNineScores[$holeNumber] = $player['score'][$holeNumber];
                        }
                        if ($player['netScore'][$holeNumber] < $firstNineNetScores[$holeNumber]) {
                            $netScore[$holeNumber] = $player['netScore'][$holeNumber];
                            $firstNineNetScores[$holeNumber] = $player['netScore'][$holeNumber];
                        }
                    }
                    if ($this->ninesPlayed == 2) {
                        for ($holeNumber = 0; $holeNumber < 9; $holeNumber++) {
                            if ($player['score'][$holeNumber + 9] < $secondNineScores[$holeNumber]) {
                                $score[$holeNumber + 9] = $player['score'][$holeNumber + 9];
                                $secondNineScores[$holeNumber] = $player['score'][$holeNumber + 9];
                            }
                            if ($player['netScore'][$holeNumber + 9] < $secondNineNetScores[$holeNumber]) {
                                $netScore[$holeNumber + 9] = $player['netScore'][$holeNumber + 9];
                                $secondNineNetScores[$holeNumber] = $player['netScore'][$holeNumber + 9];
                            }
                        }
                    }
                }
                $totalScore = array_sum($score);
                $totalNetScore = array_sum($netScore);
                $firstNineTotalScore = array_sum($firstNineScores);
                $firstNineTotalNetScore = array_sum($firstNineNetScores);
                $secondNineTotalScore = array_sum($secondNineScores);
                $secondNineTotalNetScore = array_sum($secondNineNetScores);
                
                $totalTeamScore = 0;
                $totalTeamNetScore = 0;
                $firstNineTotalTeamScore = 0;
                $firstNineTotalTeamNetScore = 0;
                $secondNineTotalTeamScore = 0;
                $secondNineTotalTeamNetScore = 0;
                
                foreach($team['players'] as $player) {
                    $totalTeamScore += $player['totalScore'];
                    $totalTeamNetScore += $player['totalNetScore'];
                    $firstNineTotalTeamScore += $player['firstNineTotalScore'];
                    $firstNineTotalTeamNetScore += $player['firstNineTotalNetScore'];
                    $secondNineTotalTeamScore += $player['secondNineTotalScore'];
                    $secondNineTotalTeamNetScore += $player['secondNineTotalNetScore'];
                }
                $team['score'] = $score;
                $team['totalScore'] = $totalScore;
                $team['totalTeamScore'] = $totalTeamScore;
                $team['netScore'] = $netScore;
                $team['totalNetScore'] = $totalNetScore;
                $team['totalTeamNetScore'] = $totalTeamNetScore;
                
                $team['firstNineScores'] = $firstNineScores;
                $team['firstNineTotalScore'] = $firstNineTotalScore;
                $team['firstNineTotalTeamScore'] = $firstNineTotalTeamScore;
                $team['firstNineNetScores'] = $firstNineNetScores;
                $team['firstNineTotalNetScore'] = $firstNineTotalNetScore;
                $team['firstNineTotalTeamNetScore'] = $firstNineTotalTeamNetScore;
                
                $team['secondNineScores'] = $secondNineScores;
                $team['secondNineTotalScore'] = $secondNineTotalScore;
                $team['secondNineTotalTeamScore'] = $secondNineTotalTeamScore;
                $team['secondNineNetScores'] = $secondNineNetScores;
                $team['secondNineTotalNetScore'] = $secondNineTotalNetScore;
                $team['secondNineTotalTeamNetScore'] = $secondNineTotalTeamNetScore;
            }
        }
        $this->sortEventResults($event);
    }
    
    /**
     * @param array $score
     * @param NineDE $nine
     * @param string $teeName
     * @param int $teamHandicapIndex
     *
     * @return array netScore
     */
    private function teamNetScore(array $score, NineDE $nine, string $teeName, int $teamHandicapIndex) : array {
        $netScore = [];
        $nineHoleHandicaps = [];
        $teamHandicap = $teamHandicapIndex * 2;
        
        $tee = $this->tee($nine, $teeName);
        foreach($tee->getHoles() as $hole) {
            $nineHoleHandicaps[] = $hole->getHandicap();
        }
        for ($holeNumber = 0; $holeNumber < 9; $holeNumber++) {
            $holeHandicap = $nineHoleHandicaps[$holeNumber];
            $holeStrokes = $score[$holeNumber];
            
            $x = intval(abs($teamHandicap / 18));
            $y = intval($teamHandicap % 18 >= $holeHandicap ? 1 : 0);
            $strokes = intval($x + $y);
            $netScore[$holeNumber] = $holeStrokes - $strokes;
        }
        return $netScore;
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
    private function eighteenHoleHandicaps(array &$firstNineHoleHandicaps, ?array &$secondNineHoleHandicaps): array {
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
        
        $course = [
            'name' => $event->getCourse()->getName(),
            'teeName' => $event->getTee()->getName(),
            'firstNineName' => $event->getNine()->getName(),
            'firstNineHoleHandicaps' => $firstNineHoleHandicaps,
            'secondNineName' => empty($event->getSecondnine()) ? null : $event->getSecondnine()->getName(),
            'secondNineHoleHandicaps' => $secondNineHoleHandicaps,
            'eighteenHoleHandicaps' => $eighteenHoleHandicaps
        ];
        
        $this->course = $course;
    }
    
    private function initializeEvent(EventDE $event): void {
        $this->withHandicapping = $event->isWithhandicapping();
        $this->isScramble = EventFormatType::isScramble($this->format);
        $this->isShamble = EventFormatType::isShamble($this->format);
        $this->isLowTeamNet = EventFormatType::isLowTeamNet($this->format);
        $this->description = EventFormatType::toString($this->format);
        
        if ($this->isLowTeamNet && !$this->withHandicapping) {
            throw new Exception("Withhandicapping must be set with Low Team Net game format");
        }
        if ($this->isLowTeamNet) {
            $this->displayNet = true;
            $this->displayTotal = true;
            $this->withHandicapPercent = 100;
            $this->description .= ' WITH ' . $this->withHandicapPercent . '% HANDICAP';
        } else if ($this->isScramble) {
            $this->displayNet = false;
            $this->displayTotal = true;
            
            if ($this->withHandicapping) {
                $this->description .= ' WITH HANDICAPPING';
            } else {
                $this-> description .= ' (LOWEST TEAM TOTAL) - NO HANDICAPPING';
            }
        } else {
            if ($this->withHandicapping) {
                $this->displayNet = true;
                $this->displayTotal = false;
                $this->withHandicapPercent = 75;
                $this-> description .= ' (LOWEST TEAM NET) WITH ' . $this->withHandicapPercent . '% HANDICAP';
            } else {
                $this->displayNet = false;
                $this->displayTotal = true;
                $this-> description .= ' (LOWEST TEAM TOTAL) - NO HANDICAPPING';
            }
        }
    }
    
    private function initializePlayer(TeamgameplayerDE $p, EventDE $event) : array {
        $scoreRepository = new ScoreRepository($this->em, $this->logger);
        $currentHandicapIndex = $scoreRepository->calculatePlayerHandicapIndex($p->getPlayer(), new DateTime())['currentHandicapIndex'];
        
        if ($event->isScramble($event->getFormat())) {
            $slope = $event->getTee()->getSlope();
            $handicap = (int) round(($currentHandicapIndex * $slope) / 113, 0, PHP_ROUND_HALF_UP);
            
            $player = [
                'name' => $p->getPlayer()->getName()->getFullname(),
                'handicap' => $handicap,
                'handicapIndex' => $currentHandicapIndex
            ];
            return $player;
        }
        if ($this->withHandicapping) {
            $currentHandicapIndex = ($currentHandicapIndex * $this->withHandicapPercent)/100;
        }
        $firstNineScore = $this->playerScore($p, $event, $currentHandicapIndex, 1);
        $firstNineTee = $firstNineScore->getTee();
        $handicap = $firstNineScore->getHandicap();
        $firstNineHandicap = $firstNineScore->getHandicap();

        if ($this->ninesPlayed == 1) {
            $firstNineTee = $firstNineScore->getTee();
            $slope = $firstNineTee->getSlope();
            $par = $firstNineTee->getPar() * 2;
            $rating = $firstNineTee->getRating() * 2;
            
            $secondNineScore = null;
            $secondNineHandicap = null;
            $secondNineTee = null;
        } else {
            $secondNineScore = $this->playerScore($p, $event, $currentHandicapIndex, 2);
            $secondNineTee = $secondNineScore->getTee();
	        $secondNineHandicap = $secondNineScore->getHandicap();

            $slope = intval(($firstNineTee->getSlope() + $secondNineTee->getSlope()) /2);
            $par = $firstNineTee->getPar() + $secondNineTee->getPar();
            $rating = $firstNineTee->getRating() + $secondNineTee->getRating();
        }
        $handicapIndex = $firstNineScore->getCurrenthandicapindex() * 2;
        $eighteenHoleHandicap = (int) round((($handicapIndex * $slope) / 113) + ($rating - $par), 0, PHP_ROUND_HALF_UP);
        
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
            'name' => $p->getPlayer()->getName()->getFullname(),
            'handicap' => $handicap,
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
            'secondNineTotalNetScore' => $this->ninesPlayed == 1 ? 0 : array_sum($secondNineNetScores)
        ];
        
        return $player;
    }
    
    private function initializeTeams(EventDE $event): void {
        $teams = [];
        
        foreach($event->getTeamgames() as $teamGame) {
            for ($teamNumber = 1; $teamNumber <= 2; $teamNumber++) {
                $players = [];
                $playerCollection = $teamNumber == 1 ? $teamGame->getTeamOnePlayersCollection() : $teamGame->getTeamTwoPlayersCollection();
                
                foreach($playerCollection as $player) {
                    $players[] = $this->initializePlayer($player, $event);
                }
                if (!empty($teamNumber == 1 ? $teamGame->getTeamone() : $teamGame->getTeamtwo())) {
                    $score = $teamNumber == 1 ? $this->unpackedScore($teamGame->getTeamonescore()) : $this->unpackedScore($teamGame->getTeamtwoscore());
                    
                    $team = [
                        'name' => $teamNumber == 1 ? $teamGame->getTeamone() : $teamGame->getTeamtwo(),
                                    
                        'score' => $score,
                        'totalScore' => array_sum($score),
                        'totalTeamScore' => 0,
                        'netScore' => [],
                        'totalNetScore' => 0,
                        'totalTeamNetScore' => 0,
                                    
                        'firstNineScores' => $this->firstNineScore($score), 
                        'firstNineTotalScore' => array_sum($this->firstNineScore($score)), 
                        'firstNineTotalTeamScore' => 0,
                        'firstNineNetScores' => [],
                        'firstNineTotalNetScore' => 0, 
                        'firstNineTotalTeamNetScore' => 0,
                                    
                        'secondNineScores' => $this->secondNineScore($score),
                        'secondNineTotalScore' => array_sum($this->secondNineScore($score)),
                        'secondNineTotalTeamScore' => 0,
                        'secondNineNetScores' => [],
                        'secondNineTotalNetScore' => 0,
                        'secondNineTotalTeamNetScore' => 0,
                                    
                        'players' => $players,
                        'place' => null,
                        'tieBreaker' => null
                    ];
                    $teams[] = $team;
                }
            }
        }
        $this->teams = $teams;
    }

    /**
     * @param EventDE $event
     */
    private function sortEventResultsOnNetScores(EventDE $event): void {
        $handicaps = $this->course['eighteenHoleHandicaps'];
        
        for ($i = 0; $i < sizeof($this->teams); $i++) {
            for ($j = $i + 1; $j < sizeof($this->teams); $j++) {
                $swap = false;
                
                if ($this->teams[$j]['totalNetScore'] < $this->teams[$i]['totalNetScore']) {
                    $swap = true;
                } else {
                    if ($this->teams[$j]['totalNetScore'] == $this->teams[$i]['totalNetScore']) {
                        for ($holeHandicap = 1; $holeHandicap <= 18; $holeHandicap++) {
                            $key = '' . $holeHandicap;
                            $holeHandicaps = $handicaps[$key];
                            
                            for ($holdHandicapIndex = 0; $holdHandicapIndex < 2; $holdHandicapIndex++) {
                                $hole = $holeHandicaps[$holdHandicapIndex] - 1;
                                
                                if ($hole > 0) {
                                    if ($this->teams[$j]['netScore'][$hole] < $this->teams[$i]['netScore'][$hole]) {
                                        $swap = true;
                                        $this->teams[$i]['tieBreaker'] = null;
                                        
                                        $nineName = $holdHandicapIndex == 0 ? $this->course['firstNineName'] : $this->course['secondNineName'];
                                        $this->teams[$j]['tieBreaker'] = $nineName . ' #' . $hole;
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
                    $team = $this->teams[$i];
                    $this->teams[$i] = $this->teams[$j];
                    $this->teams[$j] = $team;
                }
            }
        }
        $place = 1;
        for ($i = 0; $i < sizeof($this->teams); $i++) {
            for ($j = $i + 1; $j <= sizeof($this->teams); $j++) {
                if ($j == sizeof($this->teams) || $this->teams[$i]['tieBreaker'] != null) {
                    $this->teams[$i]['place'] = $place;
                    break;
                } else if ($this->teams[$j]['totalNetScore'] == $this->teams[$i]['totalNetScore']) {
                    $this->teams[$i]['place'] = $place;
                    $this->teams[$j]['place'] = $place;
                } else {
                    $this->teams[$i]['place'] = $place;
                    break;
                }
            }
            $place = $i + 2;
        }
    }
    
    /**
     * @param EventDE $event
     */
    private function sortEventResultsOnTotalTeamNetScores(EventDE $event): void {
        $handicaps = $this->course['eighteenHoleHandicaps'];
        
        for ($i = 0; $i < sizeof($this->teams); $i++) {
            for ($j = $i + 1; $j < sizeof($this->teams); $j++) {
                $swap = false;
                
                if ($this->teams[$j]['totalTeamNetScore'] < $this->teams[$i]['totalTeamNetScore']) {
                    $swap = true;
                } else {
                    if ($this->teams[$j]['totalTeamNetScore'] == $this->teams[$i]['totalTeamNetScore']) {
                        for ($holeHandicap = 1; $holeHandicap <= 18; $holeHandicap++) {
                            $key = '' . $holeHandicap;
                            $holeHandicaps = $handicaps[$key];

                            for ($holdHandicapIndex = 0; $holdHandicapIndex < 2; $holdHandicapIndex++) {
                                $hole = $holeHandicaps[$holdHandicapIndex] - 1;
                                
                                if ($hole > 0) {
                                    if ($this->teams[$j]['netScore'][$hole] < $this->teams[$i]['netScore'][$hole]) {
                                        $swap = true;
                                        $this->teams[$i]['tieBreaker'] = null;
                                        
                                        $nineName = $holdHandicapIndex == 0 ? $this->course['firstNineName'] : $this->course['secondNineName'];
                                        $this->teams[$j]['tieBreaker'] = $nineName . ' #' . $hole;
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
                    $team = $this->teams[$i];
                    $this->teams[$i] = $this->teams[$j];
                    $this->teams[$j] = $team;
                }
            }
        }
        $place = 1;
        for ($i = 0; $i < sizeof($this->teams); $i++) {
            for ($j = $i + 1; $j <= sizeof($this->teams); $j++) {
                if ($j == sizeof($this->teams) || $this->teams[$i]['tieBreaker'] != null) {
                    $this->teams[$i]['place'] = $place;
                    break;
                } else if ($this->teams[$j]['totalTeamNetScore'] == $this->teams[$i]['totalTeamNetScore']) {
                    $this->teams[$i]['place'] = $place;
                    $this->teams[$j]['place'] = $place;
                } else {
                    $this->teams[$i]['place'] = $place;
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
        
        for ($i = 0; $i < sizeof($this->teams); $i++) {
            for ($j = $i + 1; $j < sizeof($this->teams); $j++) {
                $swap = false;
                
                if ($this->teams[$j]['totalScore'] < $this->teams[$i]['totalScore']) {
                    $swap = true;
                } else {
                    if ($this->teams[$j]['totalScore'] == $this->teams[$i]['totalScore']) {
                        for ($holeHandicap = 1; $holeHandicap <= 18; $holeHandicap++) {
                            $key = '' . $holeHandicap;
                            $holeHandicaps = $handicaps[$key];
                            
                            for ($holdHandicapIndex = 0; $holdHandicapIndex < 2; $holdHandicapIndex++) {
                                $hole = $holeHandicaps[$holdHandicapIndex] - 1;
                                
                                if ($hole > 0) {
                                    if ($this->teams[$j]['score'][$hole] <= $this->teams[$i]['score'][$hole]) {
                                        $swap = true;
                                        $this->teams[$i]['tieBreaker'] = null;
                                        
                                        $nineName = $holdHandicapIndex == 0 ? $this->course['firstNineName'] : $this->course['secondNineName'];
                                        $this->teams[$j]['tieBreaker'] = $nineName . ' #' . $hole;
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
                    $team = $this->teams[$i];
                    $this->teams[$i] = $this->teams[$j];
                    $this->teams[$j] = $team;
                }
            }
        }
        $place = 1;
        for ($i = 0; $i < sizeof($this->teams); $i++) {
            for ($j = $i + 1; $j <= sizeof($this->teams); $j++) {
                if ($j == sizeof($this->teams) || $this->teams[$i]['tieBreaker'] != null) {
                    $this->teams[$i]['place'] = $place;
                    break;
                } else if ($this->teams[$j]['totalScore'] == $this->teams[$i]['totalScore']) {
                    $this->teams[$i]['place'] = $place;
                    $this->teams[$j]['place'] = $place;
                } else {
                    $this->teams[$i]['place'] = $place;
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
        if (EventFormatType::isLowTeamNet($this->format)) {
            $this->sortEventResultsOnTotalTeamNetScores($event);
        } else {
            if ($this->withHandicapping) {
                $this->sortEventResultsOnNetScores($event);
            } else {
                $this->sortEventResultsOnTotalScores($event);
            }
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
	 * @param TeamgamePlayerDE $player
	 * @param number $currentHandicapIndex
	 * @param EventDE $event
	 * @param int $nine
	 *
	 * @return NULL|ScoreDE
	 * @throws Exception
	 */
    private function playerScore(TeamgamePlayerDE $player, EventDE $event, $currentHandicapIndex, int $nine) : ?ScoreDE {
        if ($nine == 1) {
            $s = substr($player->getPlayerscore(), 0, 9);
            $nine = $event->getNine();
        } else if ($this->ninesPlayed == 1) {
            return null;
        } else {
            $s = substr($player->getPlayerscore(), 9, 9);
            $nine = $event->getSecondnine();
        }
        $score = new ScoreDE();
        $score->setPlayer($player->getPlayer());
        $score->setStrokes($s);
        $score->setTimestamp($event->getStartdateandtime());
        $score->setTee($this->tee($nine, $event->getTee()->getName()));
        $score->setCurrenthandicapindex($currentHandicapIndex);
        
        $scoreRepository = new ScoreRepository($this->em, $this->logger);
        $scores = $scoreRepository->findPlayerScores($player->getPlayer(), $event->getStartdateandtime());
        
        if (sizeof($scores) > 20) {
            $scores = array_slice($scores, 0, 20);
        }
        $scoresRecorded = sizeof($scores);
        $score->setHandicapdifferential($score->calculateHandicapDifferential($scoresRecorded));
        
        return $score;
    }
    
    /**
     * @param NineDE $nine
     * @param string $teeName
     *
     * @return ?TeeDE
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
     * @param string $score of packed integers
     *
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