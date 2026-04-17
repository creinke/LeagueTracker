<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity("App\Entity\LeagueDE")]
#[ORM\Table(name: "league")]
class LeagueDE {
    #[ORM\Id]
    #[ORM\Column(name: "id", type: "bigint", nullable: false)]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    private int $id;

    #[ORM\Column(name: "name", type: "string", length: 255, nullable: true)]
    private ?string $name;

    #[ORM\Version]
    #[ORM\Column(name: "version", type: "integer", nullable: true)]
    private int $version;

    #[ORM\ManyToMany(targetEntity: "App\Entity\CourseDE", cascade: ["refresh"], fetch: "LAZY")]
    #[ORM\JoinTable(name: "league_courses")]
    #[ORM\JoinColumn(name: "league_id", referencedColumnName: "id")]
    #[ORM\InverseJoinColumn(name: "course_id", referencedColumnName: "id", unique: true)]
    private Collection $courses;

    #[ORM\OneToMany( targetEntity: "App\Entity\PlayerDE", mappedBy: "league", cascade: ["all"], fetch: "LAZY")]
    private Collection $players;

    #[ORM\OneToMany( targetEntity: "App\Entity\SeasonDE", mappedBy: "league", cascade: ["all"], fetch: "LAZY")]
    private Collection $seasons;

    #[ORM\OneToMany( targetEntity: "App\Entity\TeamDE", mappedBy: "league", cascade: ["all"], fetch: "LAZY")]
    private Collection $teams;

    #[ORM\OneToMany( targetEntity: "App\Entity\UserDE", mappedBy: "league", cascade: ["all"], fetch: "LAZY")]
    private Collection $users;

    public function __construct() {
        $this->id = 0;
        $this->name = null;
        $this->version = 1;
        $this->courses = new ArrayCollection();
        $this->players = new ArrayCollection();
        $this->seasons = new ArrayCollection();
        $this->teams = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    public function getId(): int {
        return $this->id;
    }

    public function setId(int $id): void {
        $this->id = $id;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function setName(?string $name): void {
        $this->name = $name;
    }

    public function getVersion(): ?int {
        return $this->version;
    }

    public function setVersion(?int $version): void {
        $this->version = $version;
    }

    public function getCourses(): Collection {
        return $this->courses;
    }

    public function setCourses(Collection $courses): void {
        $this->courses = $courses;
    }

    public function getPlayers(): Collection {
        return $this->players;
    }

    public function setPlayers(Collection $players): void {
        $this->players = $players;
    }

    public function getSeasons(): Collection {
        return $this->seasons;
    }

    public function setSeasons(Collection $seasons): void {
        $this->seasons = $seasons;
    }

    public function getTeams(): Collection {
        return $this->teams;
    }

    public function setTeams(Collection $teams): void {
        $this->teams = $teams;
    }

    public function getCurrentlyActiveTeams(): array {
        $teams = [];
        foreach($this->getTeams() as $team) {
            if (!$team->isDefunct()) {
                $teams[] = $team;
            }
        }
        usort($teams, function($a, $b) { return $a->getTeamnumber() <=> $b->getTeamnumber(); });
        return $teams;
    }

    public function getUsers(): Collection {
        return $this->users;
    }

    public function setUsers(Collection $users): void {
        $this->users = $users;
    }
}