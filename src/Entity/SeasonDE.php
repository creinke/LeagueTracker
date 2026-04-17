<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use DateTime;

#[ORM\Entity(repositoryClass: "App\Repository\SeasonRepository")]
#[ORM\Table(name: "season")]
#[ORM\Index( name: "fk_season_league_id", columns: ["league_id"])]
class SeasonDE {
    #[ORM\Id]
    #[ORM\Column(name: "id", type: "bigint", nullable: false)]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    private int $id;

    #[ORM\Column(name: "enddate", type: "date", nullable: true)]
    private ?DateTime $enddate;

    #[ORM\ManyToOne( targetEntity: "App\Entity\LeagueDE", cascade: ["refresh"], inversedBy: "seasons" )]
    private LeagueDE $league;

    #[ORM\Column(name: "name", type: "string", length: 255, nullable: true)]
    private ?string $name;

    #[ORM\OneToMany( targetEntity: "App\Entity\SessionDE", mappedBy: "season", cascade: ["all"], fetch: "LAZY")]
    #[ORM\OrderBy(["startdate" => "ASC"])]
    private Collection $sessions;

    #[ORM\Column(name: "startdate", type: "date", nullable: true)]
    private ?DateTime $startdate;

    #[ORM\Version]
    #[ORM\Column(name: "version", type: "integer", nullable: true)]
    private ?int $version;

    public function __construct() {
        $this->id = 0;
        $this->version = 1;
        $this->sessions = new ArrayCollection();
    }

    public function getId(): int {
        return $this->id;
    }

    public function getEnddate(): ?DateTime {
        return $this->enddate;
    }

    public function getLeague(): LeagueDE {
        return $this->league;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function getSessions(): Collection {
        return $this->sessions;
    }

    public function getStartdate(): ?DateTime {
        return $this->startdate;
    }

    public function getVersion(): ?int {
        return $this->version;
    }

    public function setId(int $id): void {
        $this->id = $id;
    }

    public function setEnddate(?DateTime $enddate): void {
        $this->enddate = $enddate;
    }

    public function setLeague(LeagueDE $league): void {
        $this->league = $league;
    }

    public function setName(?string $name): void {
        $this->name = $name;
    }

    public function setSessions(Collection $sessions): void {
        $this->sessions = $sessions;
    }

    public function setStartdate(?DateTime $startdate): void {
        $this->startdate = $startdate;
    }

    public function setVersion(?int $version): void {
        $this->version = $version;
    }
}