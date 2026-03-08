<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity("App\Entity\TeammatchDE")]
#[ORM\Table(name: "teammatch")]
#[ORM\Index(columns: ["teamtwo_id"], name: "fk_teammatch_teamtwo_id")]
#[ORM\Index(columns: ["teamone_id"], name: "fk_teammatch_teamone_id")]
#[ORM\Index(columns: ["game_id"], name: "fk_teammatch_game_id")]
class TeammatchDE {
	#[ORM\Id]
	#[ORM\Column(name: "id", type: "bigint", nullable: false)]
	#[ORM\GeneratedValue(strategy: "AUTO")]
	private int $id;

	#[ORM\ManyToOne( targetEntity: "App\Entity\GameDE", cascade: ["refresh"], inversedBy: "teammatches" )]
	private GameDE $game;

	#[ORM\ManyToOne(targetEntity: "App\Entity\TeamDE", cascade: ["refresh"])]
	private TeamDE $teamone;

	#[ORM\ManyToOne(targetEntity: "App\Entity\TeamDE", cascade: ["refresh"])]
	private TeamDE $teamtwo;

	#[ORM\Column(name: "version", type: "integer", nullable: true)]
	private ?int $version;

	public function __construct() {
		$this->setVersion(1);
	}

	public function getId(): int {
		return $this->id;
	}

	public function getGame(): GameDE {
		return $this->game;
	}

	public function getTeamone(): TeamDE {
		return $this->teamone;
	}

	public function getTeamtwo(): TeamDE {
		return $this->teamtwo;
	}

	public function getVersion(): ?int {
		return $this->version;
	}

	public function setId(int $id): void {
		$this->id = $id;
	}

	public function setGame(GameDE $game): void {
		$this->game = $game;
	}

	public function setTeamone(TeamDE $teamone): void {
		$this->teamone = $teamone;
	}

	public function setTeamtwo(TeamDE $teamtwo): void {
		$this->teamtwo = $teamtwo;
	}

	public function setVersion(?int $version): void {
		$this->version = $version;
	}
}