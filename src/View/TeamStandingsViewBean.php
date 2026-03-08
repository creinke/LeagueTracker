<?php
namespace App\View;

class TeamStandingsViewBean {
	private int $highNetScore;
	private int $lowNetScore;
	private float $points;
	private float $pointsBehind;
	private string $teamName;
	private int $totalNetScore;
	private float $totalPoints;

    public function __construct(string $teamName) {
	    $this->highNetScore = 0;
	    $this->lowNetScore = 0;
	    $this->points = 0;
	    $this->pointsBehind = 0;
	    $this->teamName = $teamName;
	    $this->totalNetScore = 0;
	    $this->totalPoints = 0;
	}

	public function getHighNetScore(): int {
		return $this->highNetScore;
	}

	public function getLowNetScore(): int {
		return $this->lowNetScore;
	}

	public function getFormattedPoints() : string {
	    return number_format($this->points, 1);
	}

	public function getPoints(): float {
		return $this->points;
	}

	public function getFormattedPointsBehind() : string {
	    return number_format($this->pointsBehind, 1);
	}

	public function getPointsBehind(): float {
		return $this->pointsBehind;
	}

	public function getTeamName(): string {
		return $this->teamName;
	}

	public function getTotalNetScore(): int {
		return $this->totalNetScore;
	}

	public function getTotalPoints(): float {
	    return $this->totalPoints;
	}
	
	public function setHighNetScore($highNetScore): void {
		$this->highNetScore = $highNetScore;
	}

	public function setLowNetScore($lowNetScore): void {
		$this->lowNetScore = $lowNetScore;
	}

	public function setPoints($points): void {
		$this->points = $points;
	}

	public function setPointsBehind($pointsBehind): void {
		$this->pointsBehind = $pointsBehind;
	}

	public function setTeamName($teamName): void {
		$this->teamName = $teamName;
	}

	public function setTotalNetScore($totalNetScore): void {
		$this->totalNetScore = $totalNetScore;
	}
	
	public function setTotalPoints($totalPoints): void {
	    $this->totalPoints = $totalPoints;
	}
}

