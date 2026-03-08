<?php
namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;

#[ORM\Entity("App\Entity\SessionDE")]
#[ORM\Table(name: "session")]
#[ORM\Index( columns: ["season_id"], name: "fk_session_season_id" )]
class SessionDE {
	#[ORM\Id]
	#[ORM\Column(name: "id", type: "bigint", nullable: false)]
	#[ORM\GeneratedValue(strategy: "AUTO")]
	private int $id;

	#[ORM\Column(name: "enddate", type: "datetime", nullable: false)]
	private ?DateTime $enddate;

	#[ORM\OneToMany( mappedBy: "session", targetEntity: "App\Entity\EventDE", cascade: ["all"], fetch: "LAZY" )]
	#[ORM\OrderBy(["startdateandtime" => "ASC"])]
	private PersistentCollection $events;

	#[ORM\Column(name: "name", type: "string", length: 255, nullable: true)]
	private ?string $name;

	#[ORM\ManyToOne( targetEntity: "App\Entity\SeasonDE", cascade: ["refresh"], inversedBy: "sessions" )]
	private ?SeasonDE $season;

	#[ORM\Column(name: "startdate", type: "datetime", nullable: true)]
	private ?DateTime $startdate;

	#[ORM\Version]
	#[ORM\Column(name: "version", type: "integer", nullable: true)]
	private ?int $version;

	public function __construct(EntityManagerInterface $em) {
		$this->setId((int) null);
		$this->setVersion(1);
		$this->setEvents(new PersistentCollection($em, new ClassMetadata('App\Entity\EventDE'), new ArrayCollection()));
	}

	public function getEvents(): PersistentCollection {
		return $this->events;
	}

	public function getId(): int {
		return $this->id;
	}

	public function getEnddate(): ?DateTime {
		return $this->enddate;
	}

	public function setEvents(PersistentCollection $events): void {
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