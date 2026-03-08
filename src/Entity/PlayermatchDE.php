<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use App\Form\ScoreBean;
use Doctrine\ORM\PersistentCollection;

#[ORM\Entity("App\Entity\PlayermatchDE")]
#[ORM\Table(name: "playermatch")]
#[ORM\Index( columns: ["playertwo_id"], name: "fk_playermatch_playertwo_id" )]
#[ORM\Index( columns: ["playerone_id"], name: "fk_playermatch_playerone_id" )]
#[ORM\Index( columns: ["game_id"], name: "fk_playermatch_game_id" )]
class PlayermatchDE {
	#[ORM\Id]
	#[ORM\Column(name: "id", type: "bigint", nullable: false)]
	#[ORM\GeneratedValue(strategy: "AUTO")]
	private int $id;

	/**
	 * Many playermatches:1 game
	 */
	#[ORM\ManyToOne( targetEntity: "App\Entity\GameDE", cascade: ["refresh"], inversedBy: "playermatches" )]
	private ?GameDE $game;

	/**
	 * Many player matches:one player
	 */
	#[ORM\ManyToOne(targetEntity: "App\Entity\PlayerDE", cascade: ["refresh"], fetch: "EAGER")]
	private ?PlayerDE $playerone;

	/**
	 * Many player matches:one player
	 */
	#[ORM\ManyToOne(targetEntity: "App\Entity\PlayerDE", cascade: ["refresh"], fetch: "EAGER")]
	private ?PlayerDE $playertwo;

	/**
	 * Many players: many (generally 1 or 2) nine hole scores
	 */
	#[ORM\ManyToMany(targetEntity: "App\Entity\ScoreDE", cascade: ["persist", "refresh"], fetch: "EAGER")]
	#[ORM\JoinTable(name: "playermatch_scores")]
	#[ORM\JoinColumn(name: "match_id", referencedColumnName: "id")]
	#[ORM\InverseJoinColumn(name: "score_id", referencedColumnName: "id", unique: true)]
	private PersistentCollection $playerscores;

	#[ORM\Version]
	#[ORM\Column(name: "version", type: "integer", nullable: true)]
	private ?int $version;

	public function __construct() {
		$this->setId((int) null);
		$this->setVersion(1);
	}

	public function addOrUpdatePlayerScore(ScoreBean $scoreBean): void {
		$playerScore = $scoreBean->getScore();

		if (!empty($scoreBean->getSubstitutePlayer())) {
			$playerScore->setPlayer($scoreBean->getSubstitutePlayer());

			if ($this->playerone->getId() == $scoreBean->getPlayer()->getId()) {
				$this->playerone = $scoreBean->getSubstitutePlayer();
			} else if ($this->playertwo->getId() == $scoreBean->getPlayer()->getId()) {
				$this->playertwo = $scoreBean->getSubstitutePlayer();
			}
		}

		if ($playerScore->getId() == null) {
			$this->playerscores->add($playerScore);
		} else {
			foreach($this->playerscores->getKeys() as $key) {
				if ($this->playerscores->get($key)->getId() == $playerScore->getId()) {
					$this->playerscores->set($key, $playerScore);
					return;
				}
			}
		}
	}

	public function getId(): int {
		return $this->id;
	}

	public function getGame(): ?GameDE {
		return $this->game;
	}

	public function getPlayerone(): ?PlayerDE {
		return $this->playerone;
	}

	public function getPlayerscores(): PersistentCollection {
		return $this->playerscores;
	}

	public function getPlayerOneScores(): ?ArrayCollection {
		return $this->playerScores($this->playerone);
	}

	public function getPlayertwo(): ?PlayerDE {
		return $this->playertwo;
	}

	public function getPlayerTwoScores(): ?ArrayCollection {
		return $this->playerScores($this->playertwo);
	}

	public function getVersion(): ?int {
		return $this->version;
	}

	private function playerScores(PlayerDE $player): ?ArrayCollection {
		$playerScores = new ArrayCollection();

		if (!empty($this->playerscores) && $this->playerscores->count() > 0) {
			foreach ($this->playerscores as $playerScore) {
				if ($playerScore->getPlayer()->getId() == $player->getId()) {
					$playerScores->add($playerScore);
				}
			}
		}
		return $playerScores;
	}

	public function setId(int $id): void {
		$this->id = $id;
	}

	public function setGame(?GameDE $game): void {
		$this->game = $game;
	}

	public function setPlayerone(?PlayerDE $playerone): void {
		$this->playerone = $playerone;
	}

	public function setPlayertwo(?PlayerDE $playertwo): void {
		$this->playertwo = $playertwo;
	}

	public function setPlayerscores(PersistentCollection $playerscores): void {
		$this->playerscores = $playerscores;
	}

	public function setVersion(?int $version): void {
		$this->version = $version;
	}
}