<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;

#[ORM\Entity("App\Entity\CourseDE")]
#[ORM\Table(name: "course")]
#[ORM\Index( columns: ["address_id"], name: "fk_course_address_id" )]
class CourseDE {
	#[ORM\Id]
	#[ORM\Column(name: "id", type: "bigint", nullable: false)]
	#[ORM\GeneratedValue(strategy: "AUTO")]
	private int $id;

	#[ORM\Column(name: "name", type: "string", length: 255, nullable: true)]
	private ?string $name;

	#[ORM\Column(name: "website", type: "string", length: 255, nullable: true)]
	private ?string $website;

	#[ORM\Version]
	#[ORM\Column(name: "version", type: "integer", nullable: true)]
	private ?int $version;

	#[ORM\OneToOne(targetEntity: "App\Entity\AddressDE", cascade: ["all"])]
	private ?AddressDE $address;

	#[ORM\OneToMany(mappedBy: "course", targetEntity: "App\Entity\NineDE", cascade: ["all"], fetch: "EAGER")]
	private PersistentCollection $nines;

	public function __construct(EntityManagerInterface $em) {
		$this->setId((int) null);
		$this->setVersion(1);
		$this->setNines(new PersistentCollection($em, new ClassMetadata('App\Entity\NineDE'), new ArrayCollection()));
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

	public function getWebsite(): ?string {
		return $this->website;
	}

	public function setWebsite(?string $website): void {
		$this->website = $website;
	}

	public function getVersion(): ?int {
		return $this->version;
	}

	public function setVersion(?int $version): void {
		$this->version = $version;
	}

	public function getAddress(): ?AddressDE {
		return $this->address;
	}

	public function setAddress(?AddressDE $address): void {
		$this->address = $address;
	}

	public function getNines(): PersistentCollection {
		return $this->nines;
	}

	public function setNines(PersistentCollection $nines): void {
		$this->nines = $nines;
	}

	public function findNineByName(string $name): ?NineDE {
		foreach ($this->nines as $nine) {
			if ($nine->getName() === $name) {
				return $nine;
			}
		}
		return null;
	}
}