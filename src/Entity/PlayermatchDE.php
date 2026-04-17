<?php
namespace App\Entity;

use App\Form\ScoreBean;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity("App\Entity\PlayermatchDE")]
#[ORM\Table(name: "playermatch")]
#[ORM\Index( name: "fk_playermatch_playertwo_id", columns: ["playertwo_id"])]
#[ORM\Index( name: "fk_playermatch_playerone_id", columns: ["playerone_id"])]
#[ORM\Index( name: "fk_playermatch_game_id", columns: ["game_id"])]
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
    private Collection $playerscores;

    #[ORM\Version]
    #[ORM\Column(name: "version", type: "integer", nullable: true)]
    private ?int $version;

    public function __construct() {
        $this->id = 0;
        $this->version = 1;
        $this->playerscores = new ArrayCollection();
    }

    /** @noinspection PhpUnused */
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

    /** @noinspection PhpUnused */
    public function getGame(): ?GameDE {
        return $this->game;
    }

    /** @noinspection PhpUnused */
    public function getMatchPlayers(): array {
        return [$this->playerone, $this->playertwo];
    }

    public function getPlayerone(): ?PlayerDE {
        return $this->playerone;
    }

    public function getPlayerscores(): Collection {
        return $this->playerscores;
    }

    /** @noinspection PhpUnused */
    public function getPlayerOneScores(): ?ArrayCollection {
        return $this->playerScores($this->playerone);
    }

    public function getPlayertwo(): ?PlayerDE {
        return $this->playertwo;
    }

    /** @noinspection PhpUnused */
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

    public function setPlayerscores(Collection $playerscores): void {
        $this->playerscores = $playerscores;
    }

    public function setVersion(?int $version): void {
        $this->version = $version;
    }
}