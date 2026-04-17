<?php
/** @noinspection PhpGetterAndSetterCanBeReplacedWithPropertyHooksInspection */

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity("App\Entity\CountryDE")]
#[ORM\Table(name: "country")]
class CountryDE {
	#[ORM\Id]
	#[ORM\Column(name: "id", type: "bigint", length: 20)]
	#[ORM\GeneratedValue(strategy: "AUTO")]
	private int $id;

	#[ORM\Column(name: "name", type: "string", length: 255, nullable: false)]
	private ?string $name;

	#[ORM\OneToMany( targetEntity: "App\Entity\RegionDE", mappedBy: "country", cascade: ["all"], fetch: "EAGER")]
	private Collection $regions;

	#[ORM\Version]
	#[ORM\Column(name: "version", type: "integer", length: 11, nullable: false)]
	private int $version;

	public function __construct() {
		$this->id = 0;
		$this->version = 1;
		$this->regions = new ArrayCollection();
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

	public function getRegions(): Collection {
		return $this->regions;
	}

	public function setRegions(Collection $regions): void {
		$this->regions = $regions;
	}

	public function getVersion(): int {
		return $this->version;
	}

	public function setVersion(int $version): void {
		$this->version = $version;
	}
}