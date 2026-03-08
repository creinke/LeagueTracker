<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity("App\Entity\AddressDE")]
#[ORM\Table(name: "address")]
#[ORM\Index( columns: ["region_id"], name: "fk_address_region_id" )]
class AddressDE {
	#[ORM\Id]
	#[ORM\Column(name: "id", type: "bigint", nullable: false)]
	#[ORM\GeneratedValue(strategy: "AUTO")]
	private int $id;

	#[ORM\Column(name: "addressLine1", type: "string", length: 255, nullable: true)]
	private ?string $addressline1;

	#[ORM\Column(name: "addressLine2", type: "string", length: 255, nullable: true)]
	private ?string $addressline2;

	#[ORM\Column(name: "city", type: "string", length: 255, nullable: true)]
	private ?string $city;

	#[ORM\Column(name: "postalCode", type: "string", length: 255, nullable: true)]
	private ?string $postalcode;

	#[ORM\ManyToOne(targetEntity: "App\Entity\RegionDE", cascade: ["refresh"])]
	private ?RegionDE $region;

	#[ORM\Version]
	#[ORM\Column(name: "version", type: "integer", nullable: true)]
	private ?int $version;

	public function __construct() {
		$this->setVersion(1);
		$this->setId((int) null);
	}

	public function getId(): int {
		return $this->id;
	}

	public function setId(int $id): void {
		$this->id = $id;
	}

	public function getAddressline1(): ?string {
		return $this->addressline1;
	}

	public function setAddressline1(?string $addressline1): void {
		$this->addressline1 = $addressline1;
	}

	public function getAddressline2(): ?string {
		return $this->addressline2;
	}

	public function setAddressline2(?string $addressline2): void {
		$this->addressline2 = $addressline2;
	}

	public function getCity(): ?string {
		return $this->city;
	}

	public function setCity(?string $city): void {
		$this->city = $city;
	}

	public function getPostalcode(): ?string {
		return $this->postalcode;
	}

	public function setPostalcode(?string $postalcode): void {
		$this->postalcode = $postalcode;
	}

	public function getRegion(): ?RegionDE {
		return $this->region;
	}

	public function setRegion(?RegionDE $region): void {
		$this->region = $region;
	}

	public function getVersion(): ?int {
		return $this->version;
	}

	public function setVersion(?int $version): void {
		$this->version = $version;
	}
}