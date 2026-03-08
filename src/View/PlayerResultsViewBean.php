<?php
namespace App\View;

use App\Entity\NineDE;

class PlayerResultsViewBean {
    private int $adjustedHoleStrokesTotal;
    private array $adjustedNetStrokes;
    private int $handicap;
    private array $holePoints;
    private array $holeStrokes;
    private int $holeStrokesTotal;
    private float $netPoints;
    private int $netStrokesTotal;
    private NineDE $nine;
    private string $playerName;
    private float $totalHolePoints;
    private float $totalPoints;

	/**
	 * @param NineDE $nine nine the nine being played
	 * @param string $playerName playerName full name of player
	 * @param array $holeStrokes array holeStrokes strokes per hole
	 * @param int $holeStrokesTotal holeStrokesTotal total strokes without stroke control
	 * @param int $adjustedHoleStrokesTotal adjustedHoleStrokesTotal total strokes with stroke control
	 * @param int $handicap handicap at the time this score was recorded
	 * @param int $netStrokesTotal newStrokesTotal total strokes minus handicap
	 * @param array $adjustedNetStrokes array adjustedNetStrokes strokes per hole with current handicap applied, adjust against player's opponent
	 * @param array $holePoints array holePoints points taken from opponent per hole
	 * @param float $totalHolePoints totalHolePoints total points taken from opponent for all holes
	 * @param float $netPoints netPoints low net points taken from opponent, 0. .5, or 1
	 * @param float $totalPoints totalPoints total points taken from opponent, i.e., totalHolePoints + netPoints
	 */
    public function __construct(NineDE $nine, string $playerName, array $holeStrokes, int $holeStrokesTotal,
	    int $adjustedHoleStrokesTotal, int $handicap, int $netStrokesTotal, array $adjustedNetStrokes, array $holePoints,
	    float $totalHolePoints, float $netPoints, float $totalPoints) {
        $this->nine = $nine;
        $this->playerName = $playerName;
        $this->holeStrokes = $holeStrokes;
        $this->holeStrokesTotal = $holeStrokesTotal;
        $this->adjustedHoleStrokesTotal = $adjustedHoleStrokesTotal;
        $this->handicap = $handicap;
        $this->netStrokesTotal = $netStrokesTotal;
        $this->adjustedNetStrokes = $adjustedNetStrokes;
        $this->holePoints = $holePoints;
        $this->totalHolePoints = $totalHolePoints;
        $this->netPoints = $netPoints;
        $this->totalPoints = $totalPoints;
    }

    public function getAdjustedHoleStrokesTotal(): int {
        return $this->adjustedHoleStrokesTotal;
    }
    
    public function getAdjustedNetStrokes(): array {
        return $this->adjustedNetStrokes;
    }

    public function getHandicap(): int {
        return $this->handicap;
    }

    public function getHolePoints(): array {
        return $this->holePoints;
    }

    public function getHoleStrokes(): array {
        return $this->holeStrokes;
    }

    public function getHoleStrokesTotal(): int {
        return $this->holeStrokesTotal;
    }

    public function getNetPoints(): float {
        return $this->netPoints;
    }

    public function getNetStrokesTotal(): int {
        return $this->netStrokesTotal;
    }

    public function getPlayerName(): string {
        return $this->playerName;
    }

    public function getNine(): NineDE {
        return $this->nine;
    }
    
    public function getTotalHolePoints(): float {
        return $this->totalHolePoints;
    }

    public function getTotalPoints(): float {
        return $this->totalPoints;
    }

    public function setAdjustedHoleStrokesTotal(int $adjustedHoleStrokesTotal): void {
        $this->adjustedHoleStrokesTotal = $adjustedHoleStrokesTotal;
    }
    
    public function setAdjustedNetStrokes(array $adjustedNetStrokes): void {
        $this->adjustedNetStrokes = $adjustedNetStrokes;
    }

    public function setHandicap(int $handicap): void {
        $this->handicap = $handicap;
    }

    public function setHolePoints(array $holePoints): void {
        $this->holePoints = $holePoints;
    }

    public function setHoleStrokes(array $holeStrokes): void {
        $this->holeStrokes = $holeStrokes;
    }

    public function setHoleStrokesTotal(int $holeStrokesTotal): void {
        $this->holeStrokesTotal = $holeStrokesTotal;
    }

    public function setNetPoints(float $netPoints): void {
        $this->netPoints = $netPoints;
    }

    public function setNetStrokesTotal(int $netStrokesTotal): void {
        $this->netStrokesTotal = $netStrokesTotal;
    }

    public function setNine(NineDE $nine): void {
        $this->nine = $nine;
    }
    
    public function setPlayerName(string $playerName): void {
        $this->playerName = $playerName;
    }

    public function setTotalHolePoints(float $totalHolePoints): void {
        $this->totalHolePoints = $totalHolePoints;
    }

    public function setTotalPoints(float $totalPoints): void {
        $this->totalPoints = $totalPoints;
    }
}

