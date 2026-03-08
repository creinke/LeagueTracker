<?php
namespace App\View;

use App\Entity\EventDE;
use App\Entity\SeasonDE;
use App\Entity\SessionDE;

class SeasonStandingsViewBean {
    private SeasonDE $season;
    private array $seasonTeamStandings;     	// array(key=team name, value=TeamStandingsViewBean)
    private array $sessions;            	    // array(key=session name, value=SeasonDE)
    private array $sessionTeamStandings;    	// array (key=session name, value=array (key=team name, value=TeamStandingsViewBean))

    public function __construct(SeasonDE $season) {
        $this->season = $season;
        $this->seasonTeamStandings = array();
        $this->sessions = $season->getSessions()->toArray();
        $this->sessionTeamStandings = array();

        foreach($this->sessions as $session) {
            $this->sessionTeamStandings[$session->getName()] = array();
        }
    }

    public function getSeason() : SeasonDE {
        return $this->season;
    }

    public function getSeasonTeamStandings() : ?array {
        return $this->seasonTeamStandings;
    }

    public function getSessions() : ?array {
        return $this->sessions;
    }

    public function getSessionTeamStandings() : ?array {
        return $this->sessionTeamStandings;
    }

    public function getSessionTeamStandingsByName(SessionDE $session) : ?array {
        return $this->sessionTeamStandings[$session->getName()];
    }

    public function sortSeasonTeamStandings(): void {
        $this->sortTeamStandings($this->seasonTeamStandings);
    }

    public function sortSessionTeamStandings(string $sessionName): void {
        $this->sortTeamStandings($this->sessionTeamStandings[$sessionName]);
    }

    private function sortTeamStandings(array &$teamStandings): void {
        if (sizeof($teamStandings) == 0) {
            return;
        }
		// Sort array based on compareTeamStandings comparison function
        uasort($teamStandings,
            function (TeamStandingsViewBean $teamOneStandings, TeamStandingsViewBean $teamTwoStandings) {
                $teamOnePoints = $teamOneStandings->getPoints();
                $teamTwoPoints = $teamTwoStandings->getPoints();

                if ($teamOnePoints < $teamTwoPoints) {
                    return 1;
                } else if ($teamOnePoints > $teamTwoPoints) {
                    return -1;
                } else {
                    $teamOneTotalPoints = $teamOneStandings->getTotalPoints();
                    $teamTwoTotalPoints = $teamTwoStandings->getTotalPoints();
                    
                    if ($teamOneTotalPoints < $teamTwoTotalPoints) {
                        return 1;
                    } else if ($teamOneTotalPoints > $teamTwoTotalPoints) {
                        return -1;
                    } else {
                        return 0;
                    }
                }
            }
        );

        $teamStandingsIndexedArray = array_values($teamStandings);
        $leadingTeamTeamStandingsViewBean = $teamStandingsIndexedArray[0];
        $leadingTeamPoints = $leadingTeamTeamStandingsViewBean->getPoints();

        foreach($teamStandings as $teamStandingsViewBean) {
            $pointsBehind = $leadingTeamPoints - $teamStandingsViewBean->getPoints();
            $teamStandingsViewBean->setPointsBehind($pointsBehind);
        }
    }

    public function updateTeamStandingsViewBeans(EventDE $e, string $sessionName, GameResultsViewBean $gameResultsViewBean): void {
        if (!array_key_exists($sessionName, $this->sessionTeamStandings)) {
			$this->sessionTeamStandings[$sessionName] = array();
	    }
		$sessionTeamStandings = &$this->sessionTeamStandings[$sessionName];

        foreach($gameResultsViewBean->getTeamResults() as $teamResults) {
			$teamOneName = $teamResults->getTeamOneName();
			if (!array_key_exists($teamOneName, $this->seasonTeamStandings)) {
				$this->seasonTeamStandings[$teamOneName] = new TeamStandingsViewBean($teamOneName);
			}
            if (!$e->isPlayoffMatch($e->getEventtype())) {
                $this->updateTeamStandingsViewBean($this->seasonTeamStandings[$teamOneName], $teamResults->getTeamOneTotalPoints());
            }

            if (!array_key_exists($teamOneName, $this->sessionTeamStandings[$sessionName])) {
                $this->sessionTeamStandings[$sessionName][$teamOneName] = new TeamStandingsViewBean($teamOneName);
			}
            if (!$e->isPlayoffMatch($e->getEventtype())) {
                $this->updateTeamStandingsViewBean($this->sessionTeamStandings[$sessionName][$teamOneName], $teamResults->getTeamOneTotalPoints());
            }

            // Repeat for team two
			$teamTwoName = $teamResults->getTeamTwoName();
			if (!array_key_exists($teamTwoName, $this->seasonTeamStandings)) {
				$this->seasonTeamStandings[$teamTwoName] = new TeamStandingsViewBean($teamTwoName);
			}
            if (!$e->isPlayoffMatch($e->getEventtype())) {
                $this->updateTeamStandingsViewBean($this->seasonTeamStandings[$teamTwoName], $teamResults->getTeamTwoTotalPoints());
            }

            if (!array_key_exists($teamTwoName, $this->sessionTeamStandings[$sessionName])) {
                $this->sessionTeamStandings[$sessionName][$teamTwoName] = new TeamStandingsViewBean($teamTwoName);
			}
            if (!$e->isPlayoffMatch($e->getEventtype())) {
                $this->updateTeamStandingsViewBean($this->sessionTeamStandings[$sessionName][$teamTwoName], $teamResults->getTeamTwoTotalPoints());
            }
        }
    }

    private function updateTeamStandingsViewBean(TeamStandingsViewBean &$teamStandingsViewBean, float $points): void {
        $teamStandingsViewBean->setPoints($teamStandingsViewBean->getPoints() + $points);
        $teamStandingsViewBean->setTotalPoints($teamStandingsViewBean->getTotalPoints() + $points);
    }
}
