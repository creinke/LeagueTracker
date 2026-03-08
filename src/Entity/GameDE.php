<?php
namespace App\Entity;

use App\Form\ScoreBean;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;

#[ORM\Entity("App\Entity\GameDE")]
#[ORM\Table(name: "game")]
#[ORM\Index( columns: ["event_id"], name: "fk_game_event_id" )]
class GameDE {
	#[ORM\Id]
	#[ORM\Column(name: "id", type: "bigint", nullable: false)]
	#[ORM\GeneratedValue(strategy: "AUTO")]
	private int $id;

	#[ORM\ManyToOne( targetEntity: "App\Entity\EventDE", cascade: ["refresh"], inversedBy: "games" )]
	private EventDE $event;

	#[ORM\Column(name: "format", type: "boolean", nullable: true)]
	private ?bool $format;

	#[ORM\ManyToMany(targetEntity: "App\Entity\PlayerDE", cascade: ["refresh"], fetch: "EAGER")]
	#[ORM\JoinTable(name: "game_players")]
	#[ORM\JoinColumn(name: "game_id", referencedColumnName: "id")]
	#[ORM\InverseJoinColumn(name: "player_id", referencedColumnName: "id", unique: false)]
	private PersistentCollection $players;

	#[ORM\OneToMany( mappedBy: "game", targetEntity: "App\Entity\PlayermatchDE", cascade: ["all"], fetch: "EAGER" )]
	private PersistentCollection $playermatches;

	#[ORM\ManyToMany(targetEntity: "App\Entity\ScoreDE", cascade: ["persist", "refresh"], fetch: "EAGER")]
	#[ORM\JoinTable(name: "game_scores")]
	#[ORM\JoinColumn(name: "game_id", referencedColumnName: "id")]
	#[ORM\InverseJoinColumn(name: "score_id", referencedColumnName: "id", unique: true)]
	private PersistentCollection $playerscores;

	#[ORM\Column(name: "recorded", type: "boolean", nullable: true, options: ["default" => 0])]
	private ?bool $recorded = false;

	#[ORM\Column(name: "startingtime", type: "time", nullable: true)]
	private ?DateTime $startingtime;

	#[ORM\OneToMany( mappedBy: "game", targetEntity: "App\Entity\TeammatchDE", cascade: ["all"], fetch: "EAGER" )]
	private PersistentCollection $teammatches;

	private mixed $teamOneId;

	private mixed $teamTwoId;

	#[ORM\Version]
	#[ORM\Column(name: "version", type: "integer", nullable: true)]
	private ?int $version;

	public function __construct(EntityManagerInterface $em) {
		$this->setId((int) null);
		$this->setVersion(1);
		$this->setPlayers(new PersistentCollection($em, new ClassMetadata('App\Entity\PlayerDE'), new ArrayCollection()));
		$this->setPlayerscores(new PersistentCollection($em, new ClassMetadata('App\Entity\ScoreDE'), new ArrayCollection()));
		$this->setPlayermatches(new PersistentCollection($em, new ClassMetadata('App\Entity\PlayermatchDE'), new ArrayCollection()));
		$this->setTeammatches(new PersistentCollection($em, new ClassMetadata('App\Entity\TeammatchDE'), new ArrayCollection()));
	}

	public function addOrUpdatePlayerScore(ScoreBean $scoreBean): void {
		$playerScore = $scoreBean->getScore();

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

	public function getTeamOneId(): mixed {
		return $this->teamOneId;
	}

	public function getTeamTwoId(): mixed {
		return $this->teamTwoId;
	}

	public function isRecorded(): ?bool {
		return $this->recorded;
	}

	public function setRecorded(?bool $recorded): void {
		$this->recorded = $recorded;
	}

	public function setTeamOneId(mixed $teamOneId): void {
		$this->teamOneId = $teamOneId;
	}

	public function setTeamTwoId(mixed $teamTwoId): void {
		$this->teamTwoId = $teamTwoId;
	}

	public function getId(): int {
		return $this->id;
	}

	public function getEvent(): EventDE {
		return $this->event;
	}

	public function getFormat(): ?bool {
		return $this->format;
	}

	/**
	 * @return array<PlayerDE>
	 */
	public function getMatchPlayers(): array {
		$players = [];

		foreach($this->getPlayermatches() as $playerMatch) {
			$players[] = $playerMatch->getPlayerone();
			$players[] = $playerMatch->getPlayertwo();
		}
		return $players;
	}

	public function getPlayers(): PersistentCollection {
		return $this->players;
	}

	public function getPlayerscores(): PersistentCollection {
		return $this->playerscores;
	}

	public function getPlayermatches(): PersistentCollection {
		return $this->playermatches;
	}

	public function getSinglePlayerScores(PlayerDE $player): array {
		$playerScores = [];

		foreach($this->getPlayerscores() as $playerScore) {
			if ($player->getId() == $playerScore->getPlayer()->getId())  {
				$playerScores[] = $playerScore;
			}
		}
		return $playerScores;
	}

	public function getStartingtime(): ?DateTime {
		return $this->startingtime;
	}

	public function getTeammatches(): PersistentCollection {
		return $this->teammatches;
	}

	public function getVersion(): ?int {
		return $this->version;
	}

	public function playerScores(PlayerDE $player): ?ArrayCollection {
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

	public function setEvent(EventDE $event): void {
		$this->event = $event;
	}

	public function setFormat(?bool $format): void {
		$this->format = $format;
	}

	public function setPlayers(PersistentCollection $players): void {
		$this->players = $players;
	}

	public function setPlayermatches(PersistentCollection $playermatches): void {
		$this->playermatches = $playermatches;
	}

	public function setPlayerscores(PersistentCollection $playerscores): void {
		$this->playerscores = $playerscores;
	}

	public function setStartingtime(?DateTime $startingtime): void {
		$this->startingtime = $startingtime;
	}

	public function setTeammatches(PersistentCollection $teammatches): void {
		$this->teammatches = $teammatches;
	}

	public function setVersion(?int $version): void {
		$this->version = $version;
	}
}