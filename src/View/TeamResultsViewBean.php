<?php
namespace App\View;

class TeamResultsViewBean {
    private string $teamOneName;
    private float $teamOneNetPoints;
    private float $teamOnePlayerPoints;
    private float $teamOneTotalPoints;
    private string $teamTwoName;
    private float $teamTwoNetPoints;
    private float $teamTwoPlayerPoints;
    private float $teamTwoTotalPoints;

    public function __construct($teamOneName, $teamOnePlayerPoints, $teamOneTotalPoints, $teamOneNetPoints, $teamTwoName, $teamTwoPlayerPoints, $teamTwoTotalPoints, $teamTwoNetPoints) {
        $this->teamOneName = $teamOneName;
        $this->teamOnePlayerPoints = $teamOnePlayerPoints;
        $this->teamOneTotalPoints = $teamOneTotalPoints;
        $this->teamOneNetPoints = $teamOneNetPoints;

        $this->teamTwoName = $teamTwoName;
        $this->teamTwoPlayerPoints = $teamTwoPlayerPoints;
        $this->teamTwoTotalPoints = $teamTwoTotalPoints;
        $this->teamTwoNetPoints = $teamTwoNetPoints;
    }

    public function getTeamOneName(): string {
        return $this->teamOneName;
    }

    public function getTeamOneNetFormattedPoints() : string {
        return number_format($this->teamOneNetPoints, 1);
    }

    public function getTeamOneNetPoints(): float {
        return $this->teamOneNetPoints;
    }

    public function getTeamOnePlayerFormattedPoints() : string {
        return number_format($this->teamOnePlayerPoints, 1);
    }

    public function getTeamOnePlayerPoints(): float {
        return $this->teamOnePlayerPoints;
    }

    public function getTeamOneTotalFormattedPoints() : string {
        return number_format($this->teamOneTotalPoints, 1);
    }

    public function getTeamOneTotalPoints(): float {
        return $this->teamOneTotalPoints;
    }

    public function getTeamTwoName(): string {
        return $this->teamTwoName;
    }

    public function getTeamTwoNetFormattedPoints() : string {
        return number_format($this->teamTwoNetPoints, 1);
    }

    public function getTeamTwoNetPoints(): float {
        return $this->teamTwoNetPoints;
    }

    public function getTeamTwoPlayerFormattedPoints() : string {
        return number_format($this->teamTwoPlayerPoints, 1);
    }

    public function getTeamTwoPlayerPoints(): float {
        return $this->teamTwoPlayerPoints;
    }

    public function getTeamTwoTotalFormattedPoints() : string {
        return number_format($this->teamTwoTotalPoints, 1);
    }

    public function getTeamTwoTotalPoints(): float {
        return $this->teamTwoTotalPoints;
    }

    public function setTeamOneName($teamOneName): void {
        $this->teamOneName = $teamOneName;
    }

    public function setTeamOneNetPoints($teamOneNetPoints): void {
        $this->teamOneNetPoints = $teamOneNetPoints;
    }

    public function setTeamOnePlayerPoints($teamOnePlayerPoints): void {
        $this->teamOnePlayerPoints = $teamOnePlayerPoints;
    }

    public function setTeamOneTotalPoints($teamOneTotalPoints): void {
        $this->teamOneTotalPoints = $teamOneTotalPoints;
    }

    public function setTeamTwoName($teamTwoName): void {
        $this->teamTwoName = $teamTwoName;
    }

    public function setTeamTwoNetPoints($teamTwoNetPoints): void {
        $this->teamTwoNetPoints = $teamTwoNetPoints;
    }

    public function setTeamTwoPlayerPoints($teamTwoPlayerPoints): void {
        $this->teamTwoPlayerPoints = $teamTwoPlayerPoints;
    }

    public function setTeamTwoTotalPoints($teamTwoTotalPoints): void {
        $this->teamTwoTotalPoints = $teamTwoTotalPoints;
    }
}

