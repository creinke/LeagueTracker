<?php
namespace App\View;

use App\Entity\EventDE;
use App\Entity\ScoreDE;

class GameResultsViewBean {
    private array $ninesPlayed;
    private array $playerResuts;
    private array $teamMatches;
    private array $teamResults;

    /**
     * public contructor
     */
    public function __construct(EventDE $event) {
        $this->teamMatches = array();
        $this->teamResults = array();
        
        $ninesPlayed = [];
        $ninesPlayed[] = $event->getNine();
        if ($event->getSecondnine() != null) {
            $ninesPlayed[] = $event->getSecondnine();
        }
        $this->ninesPlayed = $ninesPlayed;
        
        foreach($event->getGames() as $game) {
            $teamMatches = $game->getTeammatches();
            $teamMatch = $teamMatches->get(0);
            $teamOneName = $teamMatch->getTeamone()->getName();
            $teamTwoName = $teamMatch->getTeamtwo()->getName();
            $teamOnePlayerPoints = 0;
            $teamOneTotalPoints = 0;
            $teamOneNetPoints = 0;
            $teamOneNetStrokes = 0;
            $teamTwoPlayerPoints = 0;
            $teamTwoTotalPoints = 0;
            $teamTwoNetStrokes = 0;
            $teamTwoNetPoints = 0;

            $playerMatches = [];
            
            foreach($game->getPlayerMatches() as $playerMatch) {
                $playerOneMatchResults = [];
                $playerTwoMatchResults = [];
                
                $playerOne = $playerMatch->getPlayerone();
                $playerOneName = $playerOne->getName()->getFullname();
                $playerTwo = $playerMatch->getPlayertwo();
                $playerTwoName = $playerTwo->getName()->getFullname();
                
                for ($nineIndex = 0; $nineIndex < count($this->ninesPlayed); $nineIndex++) {
                    $nine = $this->ninesPlayed[$nineIndex];
                    
                    $playerOneScore = $playerMatch->getPlayerOneScores()[$nineIndex];
                    $playerOneHoleStrokes = ScoreDE::unpack($playerOneScore->getStrokes());
                    $playerOneHoleStrokesTotal = $playerOneScore->getTotalStrokes();
                    $playerOneAdjustedHoleStrokesTotal = $playerOneScore->getAdjustedstrokesTotal();
                    $playerOneHolePoints = array();
                    $playerOneNetPoints = 0;
                    $playerOneTotalHolePoints = 0;
                    $playerOneTotalPoints = 0;
                    $playerOneAdjustedNetStrokes = array();
    
                    $playerTwoScore = $playerMatch->getPlayerTwoScores()[$nineIndex];
                    $playerTwoHoleStrokes = ScoreDE::unpack($playerTwoScore->getStrokes());
                    $playerTwoHoleStrokesTotal = $playerTwoScore->getTotalStrokes();
                    $playerTwoAdjustedHoleStrokesTotal = $playerTwoScore->getAdjustedstrokesTotal();
                    $playerTwoHolePoints = array();
                    $playerTwoNetPoints = 0;
                    $playerTwoTotalHolePoints = 0;
                    $playerTwoTotalPoints = 0;
                    $playerTwoAdjustedNetStrokes = array();
    
                    $playerOneNetStrokes = $playerOneScore->getTotalNetStrokes();
                    $playerTwoNetStrokes = $playerTwoScore->getTotalNetStrokes();
                    $playerOneHandicap = $playerOneScore->getHandicap();
                    $playerTwoHandicap = $playerTwoScore->getHandicap();
                    $strokeDifference = min( $playerTwoHandicap, $playerOneHandicap );
                    $playerOneAdjustedHandicap = $playerOneHandicap - $strokeDifference;
                    $playerTwoAdjustedHandicap = $playerTwoHandicap - $strokeDifference;
    
                    for ($holenumber = 0; $holenumber < 9; $holenumber++) {
                        if ($playerOneHoleStrokes[$holenumber] == 0 || $playerTwoHoleStrokes[$holenumber] == 0) {
                            $playerOneHolePoints[$holenumber] = (float) 0.5;
                            $playerOneTotalPoints += 0.5;
                            $playerOneTotalHolePoints += 0.5;
                            
                            $playerTwoHolePoints[$holenumber] = (float) 0.5;
                            $playerTwoTotalPoints += 0.5;
                            $playerTwoTotalHolePoints += 0.5;
                        } else {
                            $playerOneAdjustedNetHoleStrokes = $playerOneScore->calculateAdjustedNetStrokes($playerOneHoleStrokes, $holenumber, $playerOneAdjustedHandicap);
                            $playerOneAdjustedNetStrokes[] = $playerOneAdjustedNetHoleStrokes;
                            $playerTwoAdjustedNetHoleStrokes = $playerTwoScore->calculateAdjustedNetStrokes($playerTwoHoleStrokes, $holenumber, $playerTwoAdjustedHandicap);
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
                    if ($playerOneNetStrokes < $playerTwoNetStrokes) {
                        $playerOneTotalPoints += 1;
                        $playerOneNetPoints = 1;
                    } else if ($playerOneNetStrokes > $playerTwoNetStrokes) {
                        $playerTwoTotalPoints += 1;
                        $playerTwoNetPoints = 1;
                    } else {
                        $playerOneTotalPoints += .5;
                        $playerOneNetPoints += .5;
                        $playerTwoTotalPoints += .5;
                        $playerTwoNetPoints += .5;
                    }
                    $teamOnePlayerPoints += $playerOneTotalPoints;
                    $teamOneTotalPoints += $playerOneTotalPoints;
                    $teamOneNetStrokes += $playerOneNetStrokes;
                    $teamTwoPlayerPoints += $playerTwoTotalPoints;
                    $teamTwoTotalPoints += $playerTwoTotalPoints;
                    $teamTwoNetStrokes += $playerTwoNetStrokes;
                    
                    $playerOneResultsViewBean = new PlayerResultsViewBean(
                        $nine, $playerOneName, $playerOneHoleStrokes, $playerOneHoleStrokesTotal, $playerOneAdjustedHoleStrokesTotal, $playerOneHandicap, $playerOneNetStrokes,
                        $playerOneAdjustedNetStrokes, $playerOneHolePoints, $playerOneTotalHolePoints, $playerOneNetPoints, $playerOneTotalPoints);
    
                    $playerTwoResultsViewBean = new PlayerResultsViewBean(
                        $nine, $playerTwoName, $playerTwoHoleStrokes, $playerTwoHoleStrokesTotal, $playerTwoAdjustedHoleStrokesTotal, $playerTwoHandicap, $playerTwoNetStrokes,
                        $playerTwoAdjustedNetStrokes, $playerTwoHolePoints, $playerTwoTotalHolePoints, $playerTwoNetPoints, $playerTwoTotalPoints);
    
                    $playerOneMatchResults[] = $playerOneResultsViewBean;
                    $playerTwoMatchResults[] = $playerTwoResultsViewBean;
                }
                $playerMatches[] = $playerOneMatchResults;
                $playerMatches[] = $playerTwoMatchResults;
            }
            $this->teamMatches[] = $playerMatches;
            
            if ($teamOneNetStrokes < $teamTwoNetStrokes) {
                $teamOneTotalPoints += 1;
                $teamOneNetPoints = 1;
            } else if ($teamOneNetStrokes > $teamTwoNetStrokes) {
                $teamTwoTotalPoints += 1;
                $teamTwoNetPoints = 1;
            } else {
                $teamOneTotalPoints += .5;
                $teamOneNetPoints += .5;
                $teamTwoTotalPoints += .5;
                $teamTwoNetPoints += .5;
            }
            $teamResultsViewBean = new TeamResultsViewBean(
                $teamOneName, $teamOnePlayerPoints, $teamOneTotalPoints, $teamOneNetPoints, $teamTwoName, $teamTwoPlayerPoints, $teamTwoTotalPoints, $teamTwoNetPoints);
            $this->teamResults[] = $teamResultsViewBean;
        }

    }

    public function getNinesPlayed() : array {
        return $this->ninesPlayed;
    }
    
    public function getPlayerResults() : array {
        return $this->playerResuts;
    }

    public function getTeamMatches(): array {
        return $this->teamMatches;
    }
    
    public function getTeamResults() : array {
        return $this->teamResults;
    }

    public function setNinesPlayed(array $ninesPlayed): void {
        $this->ninesPlayed = $ninesPlayed;
    }
    
    public function setPlayerResults(array $playerResuts): void {
        $this->playerResuts = $playerResuts;
    }
    
    public function setTeamMatches($teamMatches): void {
        $this->teamMatches = $teamMatches;
    }
    
    public function setTeamResults(array $teamResults): void {
        $this->teamResults = $teamResults;
    }
}
