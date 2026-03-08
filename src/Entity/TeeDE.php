<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;

#[ORM\Entity("App\Entity\TeeDE")]
#[ORM\Table(name: "tee")]
#[ORM\Index(columns: ["nine_id"], name: "fk_tee_nine_id")]
class TeeDE {
	#[ORM\OneToMany( mappedBy: "tee", targetEntity: "App\Entity\HoleDE", cascade: ["all"], fetch: "EAGER" )]
	#[ORM\OrderBy(["holenumber" => "ASC"])]
	private PersistentCollection $holes;

	#[ORM\Id]
	#[ORM\Column(name: "id", type: "bigint", nullable: false)]
	#[ORM\GeneratedValue(strategy: "AUTO")]
	private int $id;

	#[ORM\Column(name: "length", type: "integer", nullable: true)]
	private ?int $length;

	#[ORM\Column(name: "name", type: "string", length: 255, nullable: true)]
	private ?string $name;

	#[ORM\ManyToOne( targetEntity: "App\Entity\NineDE", cascade: ["refresh"], inversedBy: "tees" )]
	private NineDE $nine;

	#[ORM\Column(name: "par", type: "integer", nullable: true)]
	private ?int $par;

	#[ORM\Column(name: "rating", type: "float", precision: 10, scale: 2, nullable: true)]
	private ?float $rating;

	#[ORM\Column(name: "slope", type: "integer", nullable: true)]
	private ?int $slope;

	#[ORM\Version]
	#[ORM\Column(name: "version", type: "integer", nullable: true)]
	private ?int $version;

	public function __construct(EntityManagerInterface $em) {
		$this->setId((int) null);
		$this->setVersion(1);
		$this->setHoles(new PersistentCollection($em, new ClassMetadata('App\Entity\HoleDE'), new ArrayCollection()));
	}

	public function getHoles(): PersistentCollection {
		return $this->holes;
	}

	public function getId(): int {
		return $this->id;
	}

	public function getLength(): ?int {
		return $this->length;
	}

	public function getName(): ?string {
		return $this->name;
	}

	public function getPar(): ?int {
		return $this->par;
	}

	public function getRating(): ?float {
		return $this->rating;
	}

	public function getSlope(): ?int {
		return $this->slope;
	}

	public function getVersion(): ?int {
		return $this->version;
	}

	public function getNine(): NineDE {
		return $this->nine;
	}

	public function setHoles(PersistentCollection $holes): void {
		$this->holes = $holes;
	}

	public function setId(int $id): void {
		$this->id = $id;
	}

	public function setLength(?int $length): void {
		$this->length = $length;
	}

	public function setName(?string $name): void {
		$this->name = $name;
	}

	public function setNine(NineDE $nine): void {
		$this->nine = $nine;
	}

	public function setPar(?int $par): void {
		$this->par = $par;
	}

	public function setRating(?float $rating): void {
		$this->rating = $rating;
	}

	public function setSlope(?int $slope): void {
		$this->slope = $slope;
	}

	public function setVersion(?int $version): void {
		$this->version = $version;
	}
}