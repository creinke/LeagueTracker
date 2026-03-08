<?php

namespace App\Form;

use DateTime;

/**
 * CreateScheduleFormBean
 */
class CreateScheduleFormBean {
    private int $eventType;
    private int $eventFormat;
    private int $minutesBetweenGames;
    private int $numberOfWeeks;
    private int $numberOfSessions;
    private int $playersPerTeam;
    private string $seasonName;
    private DateTime $seasonStartingDateAndTime;
    private int $startingNine;
    private int $teamsOrPlayersPerGame;
    private string $tee;
    private bool $withhandicapping;

    /**
     * @return int eventFormat
     */
    public function getEventFormat(): int {
        return $this->eventFormat;
    }
    
    /**
     * @return int eventType
     */
    public function getEventType(): int {
        return $this->eventType;
    }

    /**
     * @return int minutesBetweenGames
     */
    public function getMinutesBetweenGames(): int {
        return $this->minutesBetweenGames;
    }
    
    /**
     * @return int numberOfWeeks
     */
    public function getNumberOfWeeks(): int {
        return $this->numberOfWeeks;
    }

    /**
     * @return int numberOfSessions
     */
    public function getNumberOfSessions(): int {
        return $this->numberOfSessions;
    }

    /**
     * @return int playersPerTeam
     */
    public function getPlayersPerTeam(): int {
        return $this->playersPerTeam;
    }
    
    /**
     * @return string seasonName
     */
    public function getSeasonName(): string {
        return $this->seasonName;
    }

    /**
     * @return DateTime seasonStartingDateAndTime
     */
    public function getSeasonStartingDateAndTime(): DateTime {
        return $this->seasonStartingDateAndTime;
    }

    /**
     * @return int (id) startingNine
     */
    public function getStartingNine(): int {
        return $this->startingNine;
    }

    /**
     * @return int teamsOrPlayersPerGame
     */
    public function getTeamsOrPlayersPerGame(): int {
        return $this->teamsOrPlayersPerGame;
    }
    
    /**
     * @return string tee
     */
    public function getTee(): string {
        return $this->tee;
    }

    /**
     * @return boolean withhandicapping
     */
    public function getWithhandicapping(): bool {
        return $this->withhandicapping;
    }
    
    /**
     * @param int $eventFormat
     */
    public function setEventFormat( int $eventFormat): void {
        $this->eventFormat = $eventFormat;
    }
    
    /**
     * @param int $eventType
     */
    public function setEventType( int $eventType): void {
        $this->eventType = $eventType;
    }
    
    /**
     * @param int $minutesBetweenGames
     */
    public function setMinutesBetweenGames( int $minutesBetweenGames): void {
        $this->minutesBetweenGames = $minutesBetweenGames;
    }
    
    /**
     * @param int $numberOfWeeks
     */
    public function setNumberOfWeeks( int $numberOfWeeks): void {
        $this->numberOfWeeks = $numberOfWeeks;
    }

    /**
     * @param int $numberOfSessions
     */
    public function setNumberOfSessions( int $numberOfSessions): void {
        $this->numberOfSessions = intval($numberOfSessions);
    }

    /**
     * @param int $playersPerTeam
     */
    public function setPlayersPerTeam( int $playersPerTeam): void {
        $this->playersPerTeam = $playersPerTeam;
    }
    
    /**
     * @param string $seasonName
     */
    public function setSeasonName( string $seasonName): void {
        $this->seasonName = $seasonName;
    }

    /**
     * @param DateTime $seasonStartingDateAndTime
     */
    public function setSeasonStartingDateAndTime( DateTime $seasonStartingDateAndTime): void {
        $this->seasonStartingDateAndTime = $seasonStartingDateAndTime;
    }

    /**
     * @param int $startingNine (id) $startingNine
     */
    public function setStartingNine(int $startingNine): void {
        $this->startingNine = intval($startingNine);
    }

    /**
     * @param int $teamsOrPlayersPerGame
     */
    public function setTeamsOrPlayersPerGame( int $teamsOrPlayersPerGame): void {
        $this->teamsOrPlayersPerGame = $teamsOrPlayersPerGame;
    }
    
    /**
     * @param string $tee
     */
    public function setTee( string $tee): void {
        $this->tee = intval($tee);
    }

    /**
     * @param boolean $withhandicapping
     */
    public function setWithhandicapping( bool $withhandicapping): void {
        $this->withhandicapping = $withhandicapping;
    }
}
