<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity("App\Entity\TeamgameplayerDE")]
#[ORM\Table(name: "teamgameplayer")]
class TeamgameplayerDE {
	#[ORM\Id]
	#[ORM\Column(name: "id", type: "bigint", nullable: false)]
	#[ORM\GeneratedValue(strategy: "AUTO")]
	private int $id;

	#[ORM\Column(name: "teamnumber", type: "integer", nullable: true)]
	private ?int $teamnumber;

	#[ORM\OneToOne(targetEntity: "App\Entity\PlayerDE", cascade: ["refresh"], fetch: "EAGER")]
	private ?PlayerDE $player;

	#[ORM\Column(name: "playerscore", type: "string", length: 18, nullable: true)]
	private ?string $playerscore;

	#[ORM\Version]
	#[ORM\Column(name: "version", type: "integer", nullable: true)]
	private ?int $version;

	public function __construct(?PlayerDE $player = null, ?int $teamNumber = null) {
		$this->setId((int) null);
		$this->setVersion(1);
		$this->setPlayer($player);
		$this->setTeamnumber($teamNumber);

		$paddingCharacter = ScoreDE::packIntArray([15]);
		$this->pad($this->playerscore, $paddingCharacter, 18);
	}

	public function getFirstnine(): array {
		$nine = [];
		$strokes = ScoreDE::unpack($this->playerscore);

		for ($i = 0; $i < 9; $i++) {
			if ($strokes[$i] == 15) {
				$nine[] = null;
			} else {
				$nine[] = $strokes[$i];
			}
		}
		return $nine;
	}

	public function getId(): int {
		return $this->id;
	}

	public function getTeamnumber(): ?int {
		return $this->teamnumber;
	}

	public function getPlayer(): ?PlayerDE {
		return $this->player;
	}

	public function getPlayerscore(): ?string {
		return $this->playerscore;
	}

	public function getSecondnine(): array {
		$nine = [];
		$strokes = ScoreDE::unpack($this->playerscore);

		for ($i = 9; $i < 18; $i++) {
			if ($strokes[$i] == 15) {
				$nine[] = null;
			} else {
				$nine[] = $strokes[$i];
			}
		}
		return $nine;
	}

	public function getVersion(): ?int {
		return $this->version;
	}

	private function pad(&$s, string $paddingCharacter, int $size): void {
		while ($size-- > 0) {
			$s .= $paddingCharacter;
		}
	}

	public function setFirstnine(array $strokes): void {
		$a = ScoreDE::unpack($this->playerscore);

		for ($i = 0; $i < 9; $i++) {
			$a[$i] = $strokes[$i];
		}
		$this->playerscore = ScoreDE::packIntArray($a);
	}

	public function setSecondnine(array $strokes): void {
		$a = ScoreDE::unpack($this->playerscore);

		for ($i = 0; $i < 9; $i++) {
			$a[9 + $i] = $strokes[$i];
		}
		$this->playerscore = ScoreDE::packIntArray($a);
	}

	public function setId(int $id): void {
		$this->id = $id;
	}

	public function setPlayerscore(?string $playerscore): void {
		$this->playerscore = $playerscore;
	}

	public function setTeamnumber(?int $teamnumber): void {
		$this->teamnumber = $teamnumber;
	}

	public function setPlayer(?PlayerDE $player): void {
		$this->player = $player;
	}

	public function setVersion(?int $version): void {
		$this->version = $version;
	}
}