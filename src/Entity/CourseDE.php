<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity("App\Entity\CourseDE")]
#[ORM\Table(name: "course")]
#[ORM\Index( name: "fk_course_address_id", columns: ["address_id"])]
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

	#[ORM\OneToMany(targetEntity: "App\Entity\NineDE", mappedBy: "course", cascade: ["all"], fetch: "EAGER")]
	private Collection $nines;

	public function __construct() {
		$this->id = 0;
		$this->version = 1;
		$this->nines = new ArrayCollection();
	}

    public function __toString(): string {
        if (isset($this->name) && $this->name !== '') {
            return $this->name;
        }

        if (isset($this->id) && $this->id !== null) {
            return 'Course #' . $this->id;
        }

        return 'Course';
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

	public function getNines(): Collection {
		return $this->nines;
	}

	public function setNines(Collection $nines): void {
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