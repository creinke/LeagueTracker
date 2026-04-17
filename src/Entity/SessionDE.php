<?php
namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity("App\Entity\SessionDE")]
#[ORM\Table(name: "session")]
#[ORM\Index( name: "fk_session_season_id", columns: ["season_id"])]
class SessionDE {
    #[ORM\Id]
    #[ORM\Column(name: "id", type: "bigint", nullable: false)]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    private int $id;

    #[ORM\Column(name: "enddate", type: "datetime", nullable: false)]
    private ?DateTime $enddate;

    #[ORM\OneToMany( targetEntity: "App\Entity\EventDE", mappedBy: "session", cascade: ["all"], fetch: "LAZY")]
    #[ORM\OrderBy(["startdateandtime" => "ASC"])]
    private Collection $events;

    #[ORM\Column(name: "name", type: "string", length: 255, nullable: true)]
    private ?string $name;

    #[ORM\ManyToOne( targetEntity: "App\Entity\SeasonDE", cascade: ["refresh"], inversedBy: "sessions" )]
    private ?SeasonDE $season;

    #[ORM\Column(name: "startdate", type: "datetime", nullable: true)]
    private ?DateTime $startdate;

    #[ORM\Version]
    #[ORM\Column(name: "version", type: "integer", nullable: true)]
    private ?int $version;

    public function __construct() {
        $this->id = 0;
        $this->version = 1;
        $this->events = new ArrayCollection();
    }

    public function __toString(): string {
        if (isset($this->name) && $this->name !== '') {
            return $this->name;
        }

        if (isset($this->id) && $this->id !== null) {
            return 'Session #' . $this->id;
        }

        return 'Session';
    }

    public function getEvents(): Collection {
        return $this->events;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getEnddate(): ?DateTime {
        return $this->enddate;
    }

    public function setEvents(Collection $events): void {
        $this->events = $events;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function getSeason(): ?SeasonDE {
        return $this->season;
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

    public function setName(?string $name): void {
        $this->name = $name;
    }

    public function setSeason(?SeasonDE $season): void {
        $this->season = $season;
    }

    public function setStartdate(?DateTime $startdate): void {
        $this->startdate = $startdate;
    }

    public function setVersion(?int $version): void {
        $this->version = $version;
    }
}