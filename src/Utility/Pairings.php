<?php
namespace App\Utility;
    
use App\Entity\PlayerDE;
use \Exception;

class Pairings {
    static private function addToPlayedTables(array $team, int $randomTeamIndex, int $event, array &$playersPlayingInEvent, array &$teamsPlayedInAllEvents) {
        $playersPlayingInEvent[strval($team[0]->getId())] = true;
        $playersPlayingInEvent[strval($team[1]->getId())] = true;
        $teamsPlayedInAllEvents[strval($randomTeamIndex)] = $event;
    }
    
    static private function findRandomTeam(PlayerDE $oddPlayerOne, PlayerDE $oddPlayerTwo, array $seedTeams, ?int &$randomTeamIndex) {
        for ($i = 0; $i < sizeof($seedTeams); $i++) {
            $team = $seedTeams[$i];
            
            $playerOne = $team[0];
            $playerTwo = $team[1];
            
            if ($playerOne->getId() == $oddPlayerOne->getId() && $playerTwo->getId() == $oddPlayerTwo->getId() ||
                $playerOne->getId() == $oddPlayerTwo->getId() && $playerTwo->getId() == $oddPlayerOne->getId()) {
                    $randomTeamIndex = $i;
                    return $team;
            }
        }
        $undefinedTeam = [$oddPlayerOne, $oddPlayerTwo];
        throw new Exception("Unable to find team: " . Pairings::teamName($undefinedTeam) . PHP_EOL);
    }
    
    static public function generateRandomSinglesPairings(array $players, int $numberOfEvents, int $playersPerGame) {
        $numberOfPlayers = sizeof($players);
        $gamesPerEvent = intval(($numberOfPlayers + ($playersPerGame - 1)) / $playersPerGame);
        $gamesInAllEvents = [];
        
        for ($event = 0; $event < $numberOfEvents; $event++) {
            $games = [];
            $playersRemainingInGame = $numberOfPlayers;
            
            shuffle($players);
            $randomPlayerOffset = 0;
            
            for ($gameNumber = 0; $gameNumber < $gamesPerEvent; $gameNumber++) {
                $game = [];
                if ($playersRemainingInGame == 3 && $playersPerGame == 2) {
                    $playersInThisGame = 3;
                    $gameNumber++;
                } else if ($playersRemainingInGame == 4 && $playersPerGame == 3) {
                    $playersInThisGame = 2;
                } else if (($playersRemainingInGame == 5 || $playersRemainingInGame == 6) && $playersPerGame == 4) {
                    $playersInThisGame = 3;
                } else {
                    $playersInThisGame = min($playersRemainingInGame, $playersPerGame);
                }
                for ($playerOffset = $randomPlayerOffset; $playerOffset < $randomPlayerOffset + $playersInThisGame; $playerOffset++) {
                    $game[] = $players[$playerOffset];
                }
                $games[] = $game;
                $randomPlayerOffset += $playersInThisGame;
                $playersRemainingInGame -= $playersInThisGame;
            }
            $gamesInAllEvents[] = $games;
        }
        return $gamesInAllEvents;
    }
    
    static public function generateRandomTeamPairings(array $players, int $numberOfEvents, int $matchesPerGame) {
        $seedTeams = array();
        
        for ($i = 0, $max = count($players); $i < $max; $i++) {
            for($j = $i + 1; $j < $max; $j++) {
                $team = array($players[$i], $players[$j]);
                $seedTeams[] = $team;
            }
        }
        $numberOfSeedTeams = sizeof($seedTeams);
        
        $numberOfTeams = intval((sizeof($players) + 1) / 2);
        $gamesPerEvent = intval(($numberOfTeams + 1) / 2);
        
        $gamesInAllEvents = [];
        $teamsPlayedInAllEvents = [];
        $repeatingTeams = [];
        
        for ($event = 0; $event < $numberOfEvents; $event++) {
            $games = [];
            $playersRemainingInGame = sizeof($players);
            $playersPlayingInEvent = [];
            
            for ($gameNumber = 0; $gameNumber < $gamesPerEvent; $gameNumber++) {
                $match = [];
                $teamsRemainingInMatch = 2;
                
                while ($teamsRemainingInMatch > 0) {
                    if ($playersRemainingInGame == 1) {
                        foreach($players as $player) {
                            if (!array_key_exists(strval($player->getId()), $playersPlayingInEvent)) {
                                $oddPlayer = $player;
                                break;
                            }
                        }
                        $randomTeamIndex = 0;
                        $firstPlayerInFirstGame = $games[0][0][0];
                        $oddTeam = Pairings::findRandomTeam($oddPlayer, $firstPlayerInFirstGame, $seedTeams, $randomTeamIndex);
                        $oddTeam[2] = $firstPlayerInFirstGame;
                        $match[] = $oddTeam;
                        
                        Pairings::addToPlayedTables($oddTeam, $randomTeamIndex, $event, $playersPlayingInEvent, $teamsPlayedInAllEvents);
                    }
                    if ($playersRemainingInGame < 2 && $teamsRemainingInMatch == 1) {
                        $teamsRemainingInMatch--;
                        $playersRemainingInGame = 0;
                        
                        $oddTeam = $games[0][0];
                        $oddTeam[2] = $games[0][0][0];
                        $oddTeam[3] = $games[0][0][1];
                        $match[] = $oddTeam;
                    } else {
                        $team = Pairings::nextBestRandomTeamSelection($seedTeams, $numberOfSeedTeams, $event, $playersPlayingInEvent, $teamsPlayedInAllEvents, $repeatingTeams);
                        $match[] = $team;
                        $playersRemainingInGame -= 2;
                    }
                    $teamsRemainingInMatch--;
                }
                $games[] = $match;
            }
            $gamesInAllEvents[] = $games;
        }
        return $gamesInAllEvents;
    }
    
    /**
     * @param int $teams
     */
    static public function generateTeanMatchPairings(array $teams, int $numberOfEvents) {
        $numberOfTeams = sizeof($teams);
        $gamesPerEvent = intval(($numberOfTeams + 1) / 2);
        $tableOne = array();
        $tableTwo = array();
        
        $team = 0;
        $pairings = array();
        $pairingOffset = 0;
        
        for ($game = 0; $game < $gamesPerEvent; $game++) {
            $tableOne[$game] = ++$team;
            
            if ($game == $gamesPerEvent - 1 && $numberOfTeams % 2 != 0) {
                $tableTwo[$game] = $numberOfTeams + 1;
            } else {
                $tableTwo[$game] = ++$team;
            }
        }
        
        $teamOneGamePosition = 0;
        $matches = array();
        
        for ($event = 0; $event < $numberOfTeams - 1; $event++) {
            for ($game = 0; $game < $gamesPerEvent; $game++) {
                $teamOne = $tableOne[$game];
                $teamTwo = $tableTwo[$game];
                
                if ($teamTwo < $teamOne) {
                    $s = $teamOne;
                    $teamOne = $teamTwo;
                    $teamTwo = $s;
                }
                $match = ($teamOne == 0 ? "bye" : $teams[$teamOne - 1]->getName()) . " - " . ($teamTwo == $numberOfTeams + 1 ? "bye" : $teams[$teamTwo - 1]->getName());
                $m[$game] = array(($teamOne == 0 ? "bye" : $teams[$teamOne - 1]), ($teamTwo == $numberOfTeams + 1 ? "bye" : $teams[$teamTwo - 1]));
                $matches[$game] = $match;
            }
            $sb = "";
            
            for ($game = 0; $game < $gamesPerEvent; $game++) {
                if ($game == $teamOneGamePosition) {
                    $sb .= $matches[0] . "  ";
                    $pairings[$pairingOffset][$game] = $m[0];
                } else if ($game < $teamOneGamePosition) {
                    $sb .= $matches[$game + 1] . "  ";
                    $pairings[$pairingOffset][$game] = $m[$game + 1];
                } else {
                    $sb .= $matches[$game] . "  ";
                    $pairings[$pairingOffset][$game] = $m[$game];
                }
            }
            if (--$teamOneGamePosition < 0) {
                $teamOneGamePosition = $gamesPerEvent - 1;
            }
            // echo $sb;
            $pairingOffset++;
            Pairings::shiftTables($tableOne, $tableTwo);
        }
        $gamesInAllEvents = [];
        $pairingOffset = sizeof($pairings) - 1;
        
        for ($eventNumber = 0; $eventNumber < $numberOfEvents; $eventNumber++) {
            if (++$pairingOffset == sizeof($pairings)) {
                shuffle($pairings);
                $pairingOffset = 0;
            }
            $games = [];
            for ($gameNumber = 0; $gameNumber < $gamesPerEvent; $gameNumber++) {
                $games[] = $pairings[$pairingOffset][$gameNumber];
            }
            $gamesInAllEvents[] = $games;
        }
        return $gamesInAllEvents;
    }
    
    static private function nextBestRandomTeamSelection(array $seedTeams, int $numberOfSeedTeams, int $event, array &$playersPlayingInEvent, array &$teamsPlayedInAllEvents, &$repeatingTeams) {
        $retries = 0;
        
        do {
            do {
                $randomTeamIndex = random_int(0, $numberOfSeedTeams - 1);
                $team = $seedTeams[$randomTeamIndex];
            } while (Pairings::teamPlayersPlaying($team, $playersPlayingInEvent));
        } while (++$retries < 10 && Pairings::teamsPlaying($seedTeams, $randomTeamIndex, $teamsPlayedInAllEvents));
        
        if ($retries == 10) {
            $repeatingTeams[] = $randomTeamIndex;
        }
        Pairings::addToPlayedTables($team, $randomTeamIndex, $event, $playersPlayingInEvent, $teamsPlayedInAllEvents);
        
        return $team;
    }
    
    /**
     * @param array $tableOne
     * @param array $tableTwo
     */
    private static function shiftTables(array &$tableOne, array &$tableTwo) {
        $tableLength = sizeof($tableOne);
        $tableTwoSlotOneContents = $tableTwo[0];
        
        for ($i = 0; $i < $tableLength - 1; $i++) {
            $tableTwo[$i] = $tableTwo[$i + 1];
        }
        $tableTwo[$tableLength - 1] = $tableOne[$tableLength - 1];
        
        for ($i = $tableLength - 1; $i > 0; $i--) {
            $tableOne[$i] = $tableOne[$i - 1];
        }
        $tableOne[1] = $tableTwoSlotOneContents;
    }
    
    static private function teamPlayersPlaying(array $randomTeam, array $playersPlayingInEvent) {
        $playerOneId = strval($randomTeam[0]->getId());
        $playerTwoId = strval($randomTeam[1]->getId());
        
        return array_key_exists($playerOneId, $playersPlayingInEvent) || array_key_exists($playerTwoId, $playersPlayingInEvent);
    }
    
    static private function teamsPlaying(array $seedTeams, int $randomTeamIndex, array $teamsPlayedInAllEvents) {
        if (array_key_exists(strval($randomTeamIndex), $teamsPlayedInAllEvents)) {
            $team = $seedTeams[$randomTeamIndex];
            $event = $teamsPlayedInAllEvents[strval($randomTeamIndex)];
        }
        return array_key_exists(strval($randomTeamIndex), $teamsPlayedInAllEvents);
    }
    
    static private function teamName(array $team) {
        $playerOne = $team[0];
        $playerTwo = $team[1];
        $playerOneName = $playerOne->getName()->getFullname();
        $playerTwoName = $playerTwo->getName()->getFullname();
        
        if (sizeof($team) > 2) {
            for ($i = 2; $i < sizeof($team); $i++) {
                $surrogatePlayer = $team[$i];
                
                if ($playerOne->getId() == $surrogatePlayer->getId()) {
                    $playerOneName .= '(surrogate)';
                } else {
                    $playerTwoName .= '(surrogate)';
                }
                
            }
        }
        return $playerOneName . '/' . $playerTwoName;
    }
}