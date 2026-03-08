<?php
namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use App\Model\EventType;
use App\Model\EventFormatType;
use Exception;
use Traversable;

#[ORM\Entity("App\Entity\EventDE")]
#[ORM\Table(name: "event")]
#[ORM\Index( columns: ["course_id"], name: "fk_event_course_id" )]
#[ORM\Index( columns: ["nine_id"], name: "fk_event_nine_id" )]
#[ORM\Index( columns: ["secondnine_id"], name: "fk_event_secondnine_id" )]
#[ORM\Index( columns: ["tee_id"], name: "fk_event_tee_id" )]
#[ORM\Index( columns: ["session_id"], name: "fk_event_session_id" )]
class EventDE {
	#[ORM\Id]
	#[ORM\Column(name: "id", type: "bigint", nullable: false)]
	#[ORM\GeneratedValue(strategy: "AUTO")]
	private int $id;
	#[ORM\ManyToOne(targetEntity: "App\Entity\CourseDE", cascade: ["refresh"])]
	private ?CourseDE $course;
	#[ORM\Column(name: "mixedteesenabled", type: "boolean", nullable: true, options: ["default" => 0])]
	private ?bool $mixedteesenabled = false;
	#[ORM\Column(name: "eventnumber", type: "smallint", nullable: true)]
	private ?int $eventnumber;
	#[ORM\Column(name: "eventtype", type: "smallint", nullable: true)]
	private ?int $eventtype;
	#[ORM\Column(name: "format", type: "smallint", nullable: true)]
	private ?int $format;
	#[ORM\OneToMany( mappedBy: "event", targetEntity: "App\Entity\GameDE", cascade: ["all"], fetch: "EAGER", orphanRemoval: true )]
	#[ORM\OrderBy(["startingtime" => "ASC"])]
	private PersistentCollection $games;
	#[ORM\Column(name: "minutesbetweengames", type: "integer", nullable: false, options: ["unsigned" => true, "default" => 8])]
	private int $minutesbetweengames;
	#[ORM\ManyToOne(targetEntity: "App\Entity\NineDE", cascade: ["refresh"])]
	private ?NineDE $nine;
	#[ORM\Column(name: "playersperteam", type: "integer", nullable: false, options: ["unsigned" => true, "default" => 2])]
	private int $playersperteam;
	#[ORM\ManyToMany(targetEntity: "App\Entity\PlayerDE", cascade: ["refresh"], fetch: "EAGER")]
	#[ORM\JoinTable(name: "event_registrants")]
	#[ORM\JoinColumn(name: "event_id", referencedColumnName: "id")]
	#[ORM\InverseJoinColumn(name: "player_id", referencedColumnName: "id", unique: true)]
	private PersistentCollection $registrants;
	#[ORM\ManyToOne(targetEntity: "App\Entity\NineDE", cascade: ["refresh"])]
	private ?NineDE $secondnine;
	#[ORM\ManyToOne(targetEntity: "App\Entity\SessionDE", cascade: ["refresh"])]
	private ?SessionDE $session;
	#[ORM\Column(name: "startdateandtime", type: "datetime", nullable: true)]
	private ?DateTime $startdateandtime;
	private mixed $startdate;
	private mixed $starttime;
	#[ORM\OneToMany( mappedBy: "event", targetEntity: "App\Entity\TeamgameDE", cascade: ["all"], fetch: "EAGER", orphanRemoval: true )]
	#[ORM\OrderBy(["startingtime" => "ASC"])]
	private PersistentCollection $teamgames;
	#[ORM\Column(name: "teamspergame", type: "integer", nullable: false, options: ["unsigned" => true, "default" => 2])]
	private int $teamsorplayerspergame;
	#[ORM\ManyToOne(targetEntity: "App\Entity\TeeDE", cascade: ["refresh"])]
	private ?TeeDE $tee;
	#[ORM\Version]
	#[ORM\Column(name: "version", type: "integer", nullable: true)]
	private ?int $version;
	#[ORM\Column(name: "withhandicapping", type: "boolean", nullable: true, options: ["default" => 1])]
	private ?bool $withhandicapping = true;

	public function __construct(EntityManagerInterface $em) {
		$this->setId((int) null);
		$this->setVersion(1);
		$this->setGames(new PersistentCollection($em, new ClassMetadata('App\Entity\GameDE'), new ArrayCollection()));
		$this->setRegistrants(new PersistentCollection($em, new ClassMetadata('App\Entity\PlayerDE'), new ArrayCollection()));
	}

	public function getId(): int {
		return $this->id;
	}

	public function getCourse(): ?CourseDE {
		return $this->course;
	}

	public function getDescription(): string {
		$numberOfPlayers = match($this->playersperteam) {
			1 => 'SINGLE',
			2 => 'TWO',
			3 => 'THREE',
			4 => 'FOUR',
			default => $this->playersperteam
		};
		return EventType::toString($this->eventtype) . ': ' . $numberOfPlayers . ' PERSON ' . EventFormatType::toString($this->format);
	}

	public function getEventnumber(): ?int {
		return $this->eventnumber;
	}

	public function getEventtype(): ?int {
		return $this->eventtype;
	}

	public function getEventtypeString(): ?string {
		return EventType::toString($this->eventtype);
	}

	public function getFormat(): ?int {
		return $this->format;
	}

	public function getFormatString(): ?string {
		return EventFormatType::toString($this->format);
	}

	public function getGames(): PersistentCollection {
		return $this->games;
	}

	public function getMinutesbetweengames(): int {
		return $this->minutesbetweengames;
	}

	public function isMixedteesenabled(): bool {
		return (bool)$this->mixedteesenabled;
	}

	public function getMixedteesenabled(): bool {
		return $this->isMixedteesenabled();
	}

	public function getNine(): ?NineDE {
		return $this->nine;
	}

	public function getPlayersperteam(): int {
		return $this->playersperteam;
	}

	public function getRegistrants(): PersistentCollection {
		return $this->registrants;
	}

	public function getSecondnine(): ?NineDE {
		return $this->secondnine;
	}

	public function getSession(): ?SessionDE {
		return $this->session;
	}

	public function getStartdate(): mixed {
		return $this->startdate;
	}

	public function getStartdateandtime(): ?DateTime {
		return $this->startdateandtime;
	}

	public function getStarttime(): mixed {
		return $this->starttime;
	}

	public function getTeamgames(): PersistentCollection {
		return $this->teamgames;
	}

	public function getTeamsorplayerspergame(): int {
		return $this->teamsorplayerspergame;
	}

	public function getTee(): ?TeeDE {
		return $this->tee;
	}

	public function getVersion(): ?int {
		return $this->version;
	}

	public function isWithhandicapping(): bool {
		return (bool)$this->withhandicapping;
	}

	public function getWithhandicapping(): bool {
		return $this->isWithhandicapping();
	}

	public function isBetterBall(int $format): bool {
		return EventFormatType::isBetterBall($format);
	}

	public function isLowTeamNet(int $format): bool {
		return EventFormatType::isLowTeamNet($format);
	}

	public function isMatchPlay(int $format): bool {
		return EventFormatType::isMatchPlay($format);
	}

	public function isPlayoffMatch(int $type): bool {
		return EventType::isPlayoffMatch($type);
	}

	public function isScramble(int $format): bool {
		return EventFormatType::isScramble($format);
	}

	public function isSinglesMatch(int $type): bool {
		return EventType::isSinglesMatch($type);
	}

	public function isStrokePlay(int $format): bool {
		return EventFormatType::isStrokePlay($format);
	}

	public function isTeamEvent(int $type): bool {
		return EventType::isTeamEvent($type);
	}

	public function isTeamMatch(int $type): bool {
		return EventType::isTeamMatch($type);
	}

	public function setId(int $id): void {
		$this->id = $id;
	}

	public function setCourse(?CourseDE $course): void {
		$this->course = $course;
	}

	public function setEventnumber(?int $eventnumber): void {
		$this->eventnumber = $eventnumber;
	}

	public function setEventtype(?int $eventtype): void {
		$this->eventtype = $eventtype;
	}

	public function setFormat(?int $format): void {
		$this->format = $format;
	}

	public function setGames(PersistentCollection $games): void {
		$this->games = $games;
	}

	public function setMinutesbetweengames(int $minutesbetweengames): void {
		$this->minutesbetweengames = $minutesbetweengames;
	}

	public function setMixedteesenabled(?bool $mixedteesenabled): void {
		$this->mixedteesenabled = $mixedteesenabled;
	}

	public function setNine(?NineDE $nine): void {
		$this->nine = $nine;
	}

	public function setPlayersperteam(int $playersperteam): void {
		$this->playersperteam = $playersperteam;
	}

	public function setRegistrants(PersistentCollection $registrants): void {
		$this->registrants = $registrants;
	}

	public function setSecondnine(?NineDE $secondnine): void {
		$this->secondnine = $secondnine;
	}

	public function setSession(?SessionDE $session): void {
		$this->session = $session;
	}

	public function setStartdate(mixed $startdate): void {
		$this->startdate = $startdate;
	}

	public function setStartdateandtime(?DateTime $startdateandtime): void {
		$this->startdateandtime = $startdateandtime;
	}

	public function setStarttime(mixed $starttime): void {
		$this->starttime = $starttime;
	}

	public function setTeamsorplayerspergame(int $teamsorplayerspergame): void {
		$this->teamsorplayerspergame = $teamsorplayerspergame;
	}

	public function setTee(?TeeDE $tee): void {
		$this->tee = $tee;
	}

	public function setTeamgames(PersistentCollection $teamgames): void {
		$this->teamgames = $teamgames;
	}

	public function setNineTee(): void {
		foreach($this->getNine()->getTees() as $t) {
			if ($t->getName() == $this->getTee()->getName()) {
				$this->setTee($t);
				break;
			}
		}
	}

	public function setVersion(?int $version): void {
		$this->version = $version;
	}

	public function setWithhandicapping(?bool $withhandicapping): void {
		$this->withhandicapping = $withhandicapping;
	}

	/**
	 * @throws Exception
	 */
	public function sortedGames(): Traversable {
		$iterator = $this->getGames()->getIterator();
		$iterator->uasort(function ($first, $second) {
			return $first->getStartingtime() > $second->getStartingtime() ? 1 : -1;
		});
		return $iterator;
	}

	/**
	 * @throws Exception
	 */
	public function sortedTeamgames(): Traversable {
		$iterator = $this->getTeamgames()->getIterator();
		$iterator->uasort(function ($first, $second) {
			return $first->getStartingtime() > $second->getStartingtime() ? 1 : -1;
		});
		return $iterator;
	}
}