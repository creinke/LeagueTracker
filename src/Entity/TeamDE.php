<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;

#[ORM\Entity("App\Entity\TeamDE")]
#[ORM\Table(name: "team")]
#[ORM\Index(columns: ["league_id"], name: "fk_team_league_id")]
class TeamDE {
	#[ORM\Id]
	#[ORM\Column(name: "id", type: "bigint", nullable: false)]
	#[ORM\GeneratedValue(strategy: "AUTO")]
	private int $id;

	#[ORM\Column(name: "defunct", type: "boolean", nullable: true, options: ["default" => 0])]
	private ?bool $defunct;

	#[ORM\ManyToOne( targetEntity: "App\Entity\LeagueDE", cascade: ["refresh"], inversedBy: "teams" )]
	private LeagueDE $league;

	#[ORM\Column(name: "name", type: "string", length: 255, nullable: true)]
	private ?string $name;

	#[ORM\Column(name: "teamnumber", type: "integer", nullable: true)]
	private ?int $teamnumber;

	#[ORM\ManyToMany(targetEntity: "App\Entity\PlayerDE", cascade: ["refresh"], fetch: "EAGER")]
	#[ORM\JoinTable(name: "team_players")]
	#[ORM\JoinColumn(name: "team_id", referencedColumnName: "id")]
	#[ORM\InverseJoinColumn(name: "player_id", referencedColumnName: "id", unique: true)]
	#[ORM\OrderBy(["seedhandicapindex" => "ASC"])]
	private PersistentCollection $players;

	#[ORM\Version]
	#[ORM\Column(name: "version", type: "integer", nullable: true)]
	private ?int $version;

	public function __construct(EntityManagerInterface $em) {
		$this->setId((int) null);
		$this->setVersion(1);
		$this->setPlayers(new PersistentCollection($em, new ClassMetadata('App\Entity\PlayerDE'), new ArrayCollection()));
	}

	public function getId(): int {
		return $this->id;
	}

	public function setId(int $id): void {
		$this->id = $id;
	}

	public function isDefunct(): ?bool {
		return $this->defunct;
	}

	public function setDefunct(?bool $defunct): void {
		$this->defunct = $defunct;
	}

	public function getLeague(): LeagueDE {
		return $this->league;
	}

	public function setLeague(LeagueDE $league): void {
		$this->league = $league;
	}

	public function getName(): ?string {
		return $this->name;
	}

	public function setName(?string $name): void {
		$this->name = $name;
	}

	public function getTeamnumber(): ?int {
		return $this->teamnumber;
	}

	public function setTeamnumber(?int $teamnumber): void {
		$this->teamnumber = $teamnumber;
	}

	public function getPlayers(): PersistentCollection {
		return $this->players;
	}

	public function setPlayers(PersistentCollection $players): void {
		$this->players = $players;
	}

	public function getVersion(): ?int {
		return $this->version;
	}

	public function setVersion(?int $version): void {
		$this->version = $version;
	}
}