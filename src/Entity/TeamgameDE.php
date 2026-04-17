<?php
namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Form\TeamScoreFormBean;

#[ORM\Entity("App\Entity\GameDE")]
#[ORM\Table(name: "teamgame")]
#[ORM\Index(name: "fk_teamgame_event_id", columns: ["event_id"])]
class TeamgameDE {
    #[ORM\Id]
    #[ORM\Column(name: "id", type: "bigint", nullable: false)]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    private int $id;

    #[ORM\ManyToOne( targetEntity: "App\Entity\EventDE", cascade: ["refresh"], inversedBy: "teamgames" )]
    private EventDE $event;

    #[ORM\ManyToMany( targetEntity: "App\Entity\TeamgameplayerDE", cascade: ["all"], fetch: "EAGER", orphanRemoval: true )]
    #[ORM\JoinTable(name: "teamgame_players")]
    #[ORM\JoinColumn(name: "teamgame_id", referencedColumnName: "id")]
    #[ORM\InverseJoinColumn(name: "player_id", referencedColumnName: "id", unique: true)]
    #[ORM\OrderBy(["teamnumber" => "ASC"])]
    private Collection $players;

    /** @noinspection PhpPropertyOnlyWrittenInspection */
    private ArrayCollection $teamOnePlayers;
    private ArrayCollection $teamOnePlayerIds;
    /** @noinspection PhpPropertyOnlyWrittenInspection */
    private ArrayCollection $teamTwoPlayers;
    private ArrayCollection $teamTwoPlayerIds;

    #[ORM\Column(name: "recorded", type: "boolean", nullable: true, options: ["default" => 0])]
    private ?bool $recorded = false;

    #[ORM\Column(name: "startingtime", type: "time", nullable: true)]
    private ?DateTime $startingtime;

    #[ORM\Column(name: "teamone", type: "string", length: 255, nullable: true)]
    private ?string $teamone;

    #[ORM\Column(name: "teamonescore", type: "string", length: 18, nullable: true)]
    private ?string $teamonescore;

    #[ORM\Column(name: "teamtwo", type: "string", length: 255, nullable: true)]
    private ?string $teamtwo;

    #[ORM\Column(name: "teamtwoscore", type: "string", length: 18, nullable: true)]
    private ?string $teamtwoscore;

    #[ORM\Version]
    #[ORM\Column(name: "version", type: "integer", nullable: true)]
    private ?int $version;

    public function __construct(EventDE $event) {
        $this->id = 0;
        $this->version = 1;
        $paddingCharacter = ScoreDE::packIntArray([15]);
        $this->pad($this->teamonescore, $paddingCharacter, 18);
        $this->players = new ArrayCollection();
        $x = $event->getPlayersperteam();
        while ($x-- > 0) {
            $this->players->add(new TeamgameplayerDE(new PlayerDE(), 1));
        }
        if ($event->getPlayersperteam() < 4) {
            $this->pad($this->teamtwoscore, $paddingCharacter, 18);
            $x = $event->getPlayersperteam();
            while ($x-- > 0) {
                $this->players->add(new TeamgameplayerDE(new PlayerDE(), 2));
            }
        }
    }

    /** @noinspection PhpSameParameterValueInspection */
    private function pad(?string &$s, string $paddingCharacter, int $size): void {
        while ($size-- > 0) {
            $s .= $paddingCharacter;
        }
    }

    public function getId(): int {
        return $this->id;
    }

    public function getEvent(): EventDE {
        return $this->event;
    }

    public function getPlayers(): Collection {
        return $this->players;
    }

    /** @noinspection PhpUnused */
    public function getTeamOnePlayers(): ArrayCollection {
        return $this->teamOnePlayers;
    }

    /** @noinspection PhpUnused */
    public function getTeamOnePlayerIds(): ArrayCollection {
        return $this->teamOnePlayerIds;
    }

    /** @noinspection PhpUnused */
    public function getTeamTwoPlayers(): ArrayCollection {
        return $this->teamTwoPlayers;
    }

    /** @noinspection PhpUnused */
    public function getTeamTwoPlayerIds(): ArrayCollection {
        return $this->teamTwoPlayerIds;
    }

    /** @noinspection PhpUnused */
    public function getTeamone(): ?string {
        return $this->teamone;
    }

    private function getTeamPlayersCollection(int $teamNumber): ArrayCollection {
        $playerCollection = new ArrayCollection();
        foreach($this->players as $player) {
            if ($player->getTeamnumber() == $teamNumber) {
                $playerCollection->add($player);
            }
        }
        return $playerCollection;
    }

    private function getTeamPlayerIdsCollection(int $teamNumber): ArrayCollection {
        $playerCollection = new ArrayCollection();
        foreach($this->players as $player) {
            if ($player->getTeamnumber() == $teamNumber) {
                $playerCollection->add($player->getPlayer()->getId());
            }
        }
        return $playerCollection;
    }

    public function getTeamOnePlayersCollection(): ArrayCollection {
        return $this->getTeamPlayersCollection(1);
    }

    /** @noinspection PhpUnused */
    public function getTeamOnePlayerIdsCollection(): ArrayCollection {
        return $this->getTeamPlayerIdsCollection(1);
    }

    /** @noinspection PhpUnused */
    public function getTeamFormScoreBeanCollection(): ArrayCollection {
        $teamScoreFormBeanCollection = new ArrayCollection();
        $teamScoreFormBeanCollection->add(new TeamScoreFormBean($this->teamone, $this->getTeamonescore()));
        if (!empty($this->teamtwo)) {
            $teamScoreFormBeanCollection->add(new TeamScoreFormBean($this->teamtwo, $this->getTeamtwoscore()));
        }
        return $teamScoreFormBeanCollection;
    }

    public function getTeamTwoPlayersCollection(): ArrayCollection {
        return $this->getTeamPlayersCollection(2);
    }

    /** @noinspection PhpUnused */
    public function getTeamTwoPlayerIdsCollection(): ArrayCollection {
        return $this->getTeamPlayerIdsCollection(2);
    }

    /** @noinspection PhpUnused */
    public function getTeamTwoFormScoreBean(): TeamScoreFormBean {
        return new TeamScoreFormBean($this->getTeamtwo(), $this->getTeamtwoscore());
    }

    public function getTeamonescore(): ?string {
        return $this->teamonescore;
    }

    public function getTeamtwo(): ?string {
        return $this->teamtwo;
    }

    public function getTeamtwoscore(): ?string {
        return $this->teamtwoscore;
    }

    /** @noinspection PhpUnused */
    public function getStartingtime(): ?DateTime {
        return $this->startingtime;
    }

    public function getVersion(): int {
        return $this->version;
    }

    /** @noinspection PhpUnused */
    public function isRecorded(): bool {
        return $this->recorded;
    }

    public function setId(int $id): void {
        $this->id = $id;
    }

    /** @noinspection PhpUnused */
    public function setEvent(EventDE $event): void {
        $this->event = $event;
    }

    public function setPlayers(Collection $players): void {
        $this->players = $players;
    }

    private function setTeamPlayersCollection(ArrayCollection $players, int $teamNumber): void {
    }

    private function setTeamPlayerIdsCollection(ArrayCollection $playerids, int $teamNumber): void {
        if ($teamNumber == 1) {
            $this->teamOnePlayerIds = new ArrayCollection();
            foreach($playerids as $playerid) {
                if ($playerid > 0) {
                    $this->teamOnePlayerIds->add($playerid);
                }
            }
        } else {
            $this->teamTwoPlayerIds = new ArrayCollection();
            foreach($playerids as $playerid) {
                if ($playerid > 0) {
                    $this->teamTwoPlayerIds->add($playerid);
                }
            }
        }
    }

    /** @noinspection PhpUnused */
    public function setTeamOnePlayersCollection(ArrayCollection $players): void {
        $this->setTeamPlayersCollection($players, 1);
    }

    /** @noinspection PhpUnused */
    public function setTeamOnePlayerIdsCollection(ArrayCollection $playerids): void {
        $this->setTeamPlayerIdsCollection($playerids, 1);
    }

    /** @noinspection PhpUnused */
    public function setTeamTwoPlayersCollection(ArrayCollection $players): void {
        $this->setTeamPlayersCollection($players, 2);
    }

    /** @noinspection PhpUnused */
    public function setTeamTwoPlayerIdsCollection(ArrayCollection $playerids): void {
        $this->setTeamPlayerIdsCollection($playerids, 2);
    }

    /** @noinspection PhpUnused */
    public function setRecorded(bool $recorded): void {
        $this->recorded = $recorded;
    }

    /** @noinspection PhpUnused */
    public function setTeamone(?string $teamone): void {
        $this->teamone = $teamone;
    }

    public function setTeamonescore(?string $teamonescore): void {
        $this->teamonescore = $teamonescore;
    }

    /** @noinspection PhpUnused */
    public function setTeamOneScoreFormBean(TeamScoreFormBean $teamScoreFormBean): void {
        $this->setTeamonescore($teamScoreFormBean->getTeamscore());
    }

    /** @noinspection PhpUnused */
    public function setTeamtwo(?string $teamtwo): void {
        $this->teamtwo = $teamtwo;
    }

    public function setTeamtwoscore(?string $teamtwoscore): void {
        $this->teamtwoscore = $teamtwoscore;
    }

    /** @noinspection PhpUnused */
    public function setTeamTwoScoreFormBean(TeamScoreFormBean $teamScoreFormBean): void {
        $this->setTeamtwoscore($teamScoreFormBean->getTeamscore());
    }

    /** @noinspection PhpUnused */
    public function setTeamFormScoreBeanCollection(ArrayCollection $teamScoreFormBeanCollection): void {
        foreach($teamScoreFormBeanCollection as $teamScoreFormBean) {
            if ($teamScoreFormBean->getTeamname() == $this->teamone) {
                $this->setTeamonescore($teamScoreFormBean->getTeamscore());
            } else {
                $this->setTeamtwoscore($teamScoreFormBean->getTeamscore());
            }
        }
    }

    /** @noinspection PhpUnused */
    public function setStartingtime(?DateTime $startingtime): void {
        $this->startingtime = $startingtime;
    }

    public function setVersion(int $version): void {
        $this->version = $version;
    }
}
