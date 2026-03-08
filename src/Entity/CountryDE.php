<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
#[ORM\Entity("App\Entity\CountryDE")]
#[ORM\Table(name: "country")]
class CountryDE {
	#[ORM\Id]
	#[ORM\Column(name: "id", type: "bigint", length: 20)]
	#[ORM\GeneratedValue(strategy: "AUTO")]
	private int $id;

	#[ORM\Column(name: "name", type: "string", length: 255, nullable: false)]
	private ?string $name;

	#[ORM\OneToMany( mappedBy: "country", targetEntity: "App\Entity\RegionDE", cascade: ["all"], fetch: "EAGER" )]
	private PersistentCollection $regions;

	#[ORM\Version]
	#[ORM\Column(name: "version", type: "integer", length: 11, nullable: false)]
	private int $version;

	public function __construct(EntityManagerInterface $em) {
		$this->setId((int) null);
		$this->setVersion(1);
		$this->setRegions(new PersistentCollection($em, new ClassMetadata('App\Entity\RegionDE'), new ArrayCollection()));
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

	public function getRegions(): PersistentCollection {
		return $this->regions;
	}

	public function setRegions(PersistentCollection $regions): void {
		$this->regions = $regions;
	}

	public function getVersion(): int {
		return $this->version;
	}

	public function setVersion(int $version): void {
		$this->version = $version;
	}
}