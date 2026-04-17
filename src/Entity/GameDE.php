<?php
namespace App\Entity;

use App\Form\ScoreBean;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\GameRepository")]
#[ORM\Table(name: "game")]
#[ORM\Index( name: "fk_game_event_id", columns: ["event_id"])]
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
    private Collection $players;

    #[ORM\OneToMany( targetEntity: "App\Entity\PlayermatchDE", mappedBy: "game", cascade: ["all"], fetch: "EAGER")]
    private Collection $playermatches;

    #[ORM\ManyToMany(targetEntity: "App\Entity\ScoreDE", cascade: ["persist", "refresh"], fetch: "EAGER")]
    #[ORM\JoinTable(name: "game_scores")]
    #[ORM\JoinColumn(name: "game_id", referencedColumnName: "id")]
    #[ORM\InverseJoinColumn(name: "score_id", referencedColumnName: "id", unique: true)]
    private Collection $playerscores;

    #[ORM\Column(name: "recorded", type: "boolean", nullable: true, options: ["default" => 0])]
    private ?bool $recorded = false;

    #[ORM\Column(name: "startingtime", type: "time", nullable: true)]
    private ?DateTime $startingtime;

    #[ORM\OneToMany( targetEntity: "App\Entity\TeammatchDE", mappedBy: "game", cascade: ["all"], fetch: "EAGER")]
    private Collection $teammatches;

    private mixed $teamOneId;

    private mixed $teamTwoId;

    #[ORM\Version]
    #[ORM\Column(name: "version", type: "integer", nullable: true)]
    private ?int $version;

    public function __construct() {
        $this->id = 0;
        $this->version = 1;
        $this->players = new ArrayCollection();
        $this->playerscores = new ArrayCollection();
        $this->playermatches = new ArrayCollection();
        $this->teammatches = new ArrayCollection();
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

    /** @noinspection PhpUnused */
    public function getTeamOneId(): mixed {
        return $this->teamOneId;
    }

    /** @noinspection PhpUnused */
    public function getTeamTwoId(): mixed {
        return $this->teamTwoId;
    }

    public function isRecorded(): ?bool {
        return $this->recorded;
    }

    public function setRecorded(?bool $recorded): void {
        $this->recorded = $recorded;
    }

    /** @noinspection PhpUnused */
    public function setTeamOneId(mixed $teamOneId): void {
        $this->teamOneId = $teamOneId;
    }

    /** @noinspection PhpUnused */
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

    public function getPlayers(): Collection {
        return $this->players;
    }

    public function getPlayerscores(): Collection {
        return $this->playerscores;
    }

    public function getPlayermatches(): Collection {
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

    /** @noinspection PhpUnused */
    public function getStartingtime(): ?DateTime {
        return $this->startingtime;
    }

    public function getTeammatches(): Collection {
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

    public function setPlayers(Collection $players): void {
        $this->players = $players;
    }

    public function setPlayermatches(Collection $playermatches): void {
        $this->playermatches = $playermatches;
    }

    /** @noinspection PhpUnused */
    public function setPlayerscores(Collection $playerscores): void {
        $this->playerscores = $playerscores;
    }

    public function setStartingtime(?DateTime $startingtime): void {
        $this->startingtime = $startingtime;
    }

    public function setTeammatches(Collection $teammatches): void {
        $this->teammatches = $teammatches;
    }

    public function setVersion(?int $version): void {
        $this->version = $version;
    }
}