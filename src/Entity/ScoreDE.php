<?php
namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity("App\Entity\ScoreDE")]
#[ORM\Table(name: "score")]
#[ORM\Index( name: "fk_score_player_id", columns: ["player_id"])]
#[ORM\Index( name: "fk_score_tee_id", columns: ["tee_id"])]
class ScoreDE {
    #[ORM\Id]
    #[ORM\Column(name: "id", type: "bigint", nullable: false)]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    private ?int $id;

    #[ORM\Column(name: "adjustedstrokes", type: "string", length: 9, nullable: true)]
    private ?string $adjustedstrokes;

    #[ORM\Column(name: "currenthandicapindex", type: "float", precision: 10, scale: 0, nullable: true)]
    private ?float $currenthandicapindex;

    #[ORM\Column(name: "duplicatescore", type: "boolean", nullable: true, options: ["default" => 0])]
    private ?bool $duplicatescore = false;

    #[ORM\Column(name: "fairwayshit", type: "string", length: 9, nullable: true)]
    private ?string $fairwayshit;

    #[ORM\Column(name: "girs", type: "string", length: 9, nullable: true)]
    private ?string $girs;

    #[ORM\Column(name: "handicapdifferential", type: "float", precision: 10, scale: 0, nullable: true)]
    private ?float $handicapdifferential;

    #[ORM\OneToOne(targetEntity: "App\Entity\PlayerDE", cascade: ["refresh"])]
    private ?PlayerDE $player;

    #[ORM\Column(name: "partialscore", type: "boolean", nullable: true, options: ["default" => 0])]
    private ?bool $partialscore = false;

    #[ORM\Column(name: "putts", type: "string", length: 9, nullable: true)]
    private ?string $putts;

    #[ORM\Column(name: "strokes", type: "string", length: 9, nullable: true)]
    private ?string $strokes;

    #[ORM\OneToOne(targetEntity: "App\Entity\TeeDE", cascade: ["refresh"])]
    private TeeDE $tee;

    #[ORM\Column(name: "timestamp", type: "datetime", nullable: true)]
    private ?DateTime $timestamp;

    #[ORM\Column(name: "timezone", type: "string", nullable: true)]
    private ?string $timezone;

    #[ORM\Column(name: "upAndDowns", type: "string", length: 9, nullable: true)]
    private ?string $upanddowns;

    #[ORM\Version]
    #[ORM\Column(name: "version", type: "integer", nullable: true)]
    private ?int $version;

    public function __construct(?string $scores = null) {
        $this->id = 0;
        $this->version = 1;
        if ($scores) {
            $this->strokes = self::pack($scores);
        }
    }

    public function adjustStrokes(int $handicap, int $scoresRecorded): string {
        $adjustedStrokes = "";
        $strokes = self::unpack($this->getStrokes());
        $holes = $this->getTee()->getHoles();

        for ($holeOffset = 0; $holeOffset < 9; $holeOffset++) {
            $hole = $holes[$holeOffset];
            $strokesOnHole = $strokes[$holeOffset];
            $adjustedStrokesOnHole = $this->adjustedStrokesForHole($hole, $strokesOnHole, $handicap, $scoresRecorded);
            $adjustedStrokes .= $adjustedStrokesOnHole . ', ';
        }
        $s = substr($adjustedStrokes, 0, strlen($adjustedStrokes) - 2);
        return self::pack($s);
    }

    private function adjustedStrokesForHole(HoleDE $hole, int $strokes, int $handicap, int $scoresRecorded): int {
        $par = $hole->getPar();
        $holeHandicap = $hole->getHandicap();
        /** @noinspection PhpUnusedLocalVariableInspection */
        $maxScore = 0;

        if ($scoresRecorded < 3) {
            $maxScore = $par + 5;
        } else {
            $x = intval(abs($handicap / 9));
            /** @noinspection PhpCastIsUnnecessaryInspection */
            $y = intval(($handicap % 9) * 2 >= $holeHandicap ? 1 : 0);
            /** @noinspection PhpCastIsUnnecessaryInspection */
            $strokesReceived = intval($x + $y);
            $maxScore = $par + 2 + $strokesReceived;
        }
        return min($strokes, $maxScore);
    }

    public function calculateHandicapDifferential(int $scoresRecorded, float $pcc = 0): float {
        $rating = $this->getTee()->getRating();
        $slope = $this->getTee()->getSlope();
        $handicap = $this->getHandicap();

        $this->setAdjustedStrokes($this->adjustStrokes($handicap, $scoresRecorded));
        $adjustedStrokesTotal = $this->calculateAdjustedStrokesTotal();

        $i = round((($adjustedStrokesTotal - $rating - $pcc) * (113 / $slope)) * 10);
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        /** @noinspection PhpCastIsUnnecessaryInspection */
        $handicapDifferential = (float) $i / 10;
        return $handicapDifferential;
    }

    private function calculateAdjustedStrokesTotal(): int {
        $totalAdjustedStrokes = 0;
        $adjustedStrokes = self::unpack($this->getAdjustedStrokes());

        for ($holeOffset = 0; $holeOffset < 9; $holeOffset++) {
            $strokes = $adjustedStrokes[$holeOffset];
            $totalAdjustedStrokes += $strokes;
        }
        return $totalAdjustedStrokes;
    }

    public function calculateAdjustedNetStrokes(array $adjustedStrokes, int $holeNumber, int $handicap): int {
        $hole = $this->getTee()->getHoles()[$holeNumber];
        $holeHandicap = $hole->getHandicap();

        $holeStrokes = $adjustedStrokes[$holeNumber];
        $x = intval(abs($handicap / 9));
        /** @noinspection PhpCastIsUnnecessaryInspection */
        $y = intval(($handicap % 9) * 2 >= $holeHandicap ? 1 : 0);
        /** @noinspection PhpCastIsUnnecessaryInspection */
        $strokes = intval($x + $y);
        return $holeStrokes - $strokes;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getAdjustedStrokes(): ?string {
        return $this->adjustedstrokes;
    }

    /** @noinspection PhpUnused */
    public function getAdjustedStrokesTotal(): int {
        return $this->calculateAdjustedStrokesTotal();
    }

    public function getCurrentHandicapIndex(): ?float {
        return $this->currenthandicapindex;
    }

    /** @noinspection PhpUnused */
    public function getDuplicateScore(): ?bool {
        return $this->duplicatescore;
    }

    /** @noinspection PhpUnused */
    public function getFairwaysHit(): ?string {
        return $this->fairwayshit;
    }

    /** @noinspection PhpUnused */
    public function getGirs(): ?string {
        return $this->girs;
    }

    public function getHandicap(): int {
        $tee = $this->getTee();
        $slope = $tee->getSlope();
        $par = $tee->getPar();
        $rating = $tee->getRating();

        $handicap = round((($this->getCurrenthandicapindex() * $slope) / 113) + ($rating - $par));
        return $handicap == -0 ? 0 : $handicap;
    }

    /** @noinspection PhpUnused */
    public function getHandicapDifferential(): ?float {
        return $this->handicapdifferential;
    }

    /** @noinspection PhpUnused */
    public function getNetStrokes(array $strokes, int $handicap): array {
        $netStrokes = [];
        for ($holeNumber = 0; $holeNumber < 9; $holeNumber++) {
            $netStrokes[] = $this->calculateAdjustedNetStrokes($strokes, $holeNumber, $handicap);
        }
        return $netStrokes;
    }

    public function getPlayer(): ?PlayerDE {
        return $this->player;
    }

    /** @noinspection PhpUnused */
    public function getPartialScore(): ?bool {
        return $this->partialscore;
    }

    /** @noinspection PhpUnused */
    public function getPutts(): ?string {
        return $this->putts;
    }

    public function getStrokes(): ?string {
        return $this->strokes;
    }

    public function getTee(): TeeDE {
        return $this->tee;
    }

    public function getTimestamp(): DateTime {
        return $this->timestamp;
    }

    public function getTimezone(): ?string {
        return $this->timezone;
    }

    /** @noinspection PhpUnused */
    public function getTotalStrokes(): int {
        $totalStrokes = 0;
        if (!empty($this->getStrokes())) {
            $strokes = self::unpack($this->getStrokes());
            for ($hole = 0; $hole < 9; $hole++) {
                $totalStrokes += $strokes[$hole];
            }
        }
        return $totalStrokes;
    }

    /** @noinspection PhpUnused */
    public function getTotalNetStrokes(): int {
        $totalNetStrokes = 0;
        $adjustedStrokes = self::unpack($this->getAdjustedStrokes());
        for ($holeNumber = 0; $holeNumber < 9; $holeNumber++) {
            $totalNetStrokes += $this->calculateAdjustedNetStrokes($adjustedStrokes, $holeNumber, $this->getHandicap());
        }
        return $totalNetStrokes;
    }

    /** @noinspection PhpUnused */
    public function getUpAndDowns(): ?string {
        return $this->upanddowns;
    }

    public function getVersion(): ?int {
        return $this->version;
    }

    public static function pack(?string $scores): ?string {
        if (!empty($scores)) {
            $a = explode(', ', $scores);
            return self::packIntArray($a);
        }
        return null;
    }

    public static function packIntArray(array $a): string {
        $l = sizeof($a);
        $s = "";
        for ($i = 0; $i < $l; $i++) {
            $x = intval($a[$i]);
            $s .= sprintf('%1x', $x);
        }
        return $s;
    }

    public function setId($id): void {
        $this->id = $id;
    }

    public function setAdjustedStrokes($adjustedstrokes): void {
        $this->adjustedstrokes = $adjustedstrokes;
    }

    /** @noinspection PhpUnused */
    public function setCurrentHandicapIndex($currenthandicapindex): void {
        $this->currenthandicapindex = $currenthandicapindex;
    }

    /** @noinspection PhpUnused */
    public function setDuplicateScore($duplicatescore): void {
        $this->duplicatescore = $duplicatescore;
    }

    /** @noinspection PhpUnused */
    public function setFairwaysHit($fairwayshit): void {
        $this->fairwayshit = $fairwayshit;
    }

    /** @noinspection PhpUnused */
    public function setGirs($girs): void {
        $this->girs = $girs;
    }

    /** @noinspection PhpUnused */
    public function setHandicapDifferential($handicapdifferential): void {
        $this->handicapdifferential = $handicapdifferential;
    }

    public function setPlayer(PlayerDE $player): void {
        $this->player = $player;
    }

    /** @noinspection PhpUnused */
    public function setPartialScore($partialscore): void {
        $this->partialscore = $partialscore;
    }

    /** @noinspection PhpUnused */
    public function setPutts($putts): void {
        $this->putts = $putts;
    }

    public function setStrokes($strokes): void {
        $this->strokes = $strokes;
    }

    public function setTee(TeeDE $tee): void {
        $this->tee = $tee;
    }

    public function setTimestamp(DateTime $timestamp): void {
        $this->timestamp = $timestamp;
    }

    public function setTimezone($timezone): void {
        $this->timezone = $timezone;
    }

    /** @noinspection PhpUnused */
    public function setUpAndDowns($upanddowns): void {
        $this->upanddowns = $upanddowns;
    }

    public function setVersion($version): void {
        $this->version = $version;
    }

    public static function unpack(string $s): array {
        $a = [];
        $l = strlen($s);
        for ($i = 0; $i < $l; $i++) {
            $h = substr($s, $i, 1);
            $a[] = hexdec($h);
        }
        return $a;
    }
}