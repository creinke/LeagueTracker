<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;

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
	private PersistentCollection $courses;

	#[ORM\OneToMany( mappedBy: "league", targetEntity: "App\Entity\PlayerDE", cascade: ["all"], fetch: "LAZY" )]
	private PersistentCollection $players;

	#[ORM\OneToMany( mappedBy: "league", targetEntity: "App\Entity\SeasonDE", cascade: ["all"], fetch: "LAZY" )]
	private PersistentCollection $seasons;

	#[ORM\OneToMany( mappedBy: "league", targetEntity: "App\Entity\TeamDE", cascade: ["all"], fetch: "LAZY" )]
	private PersistentCollection $teams;

	#[ORM\OneToMany( mappedBy: "league", targetEntity: "App\Entity\UserDE", cascade: ["all"], fetch: "LAZY" )]
	private PersistentCollection $users;

	public function __construct(EntityManagerInterface $em) {
		$this->setId((int) null);
		$this->setName(null);
		$this->version = 1;
		$this->initializeCollection($em, 'App\Entity\CourseDE', 'courses');
		$this->initializeCollection($em, 'App\Entity\PlayerDE', 'players');
		$this->initializeCollection($em, 'App\Entity\SeasonDE', 'seasons');
		$this->initializeCollection($em, 'App\Entity\TeamDE', 'teams');
		$this->initializeCollection($em, 'App\Entity\UserDE', 'users');
	}

	private function initializeCollection(EntityManagerInterface $em, string $entityClass, string $property): void {
		$this->$property = new PersistentCollection($em, new ClassMetadata($entityClass), new ArrayCollection());
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

	public function getCourses(): PersistentCollection {
		return $this->courses;
	}

	public function setCourses(PersistentCollection $courses): void {
		$this->courses = $courses;
	}

	public function getPlayers(): PersistentCollection {
		return $this->players;
	}

	public function setPlayers(PersistentCollection $players): void {
		$this->players = $players;
	}

	public function getSeasons(): PersistentCollection {
		return $this->seasons;
	}

	public function setSeasons(PersistentCollection $seasons): void {
		$this->seasons = $seasons;
	}

	public function getTeams(): PersistentCollection {
		return $this->teams;
	}

	public function setTeams(PersistentCollection $teams): void {
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

	public function getUsers(): PersistentCollection {
		return $this->users;
	}

	public function setUsers(PersistentCollection $users): void {
		$this->users = $users;
	}
}