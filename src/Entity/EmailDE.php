<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity("App\Entity\EmailDE")]
#[ORM\Table(name: "email")]
class EmailDE {
	#[ORM\Id]
	#[ORM\Column(name: "id", type: "bigint", nullable: false)]
	#[ORM\GeneratedValue(strategy: "AUTO")]
	private int $id;

	#[ORM\Column(name: "address", type: "string", length: 255, nullable: true)]
	private ?string $address;

	#[ORM\Column(name: "type", type: "integer", nullable: true)]
	private ?int $type;

	#[ORM\Version]
	#[ORM\Column(name: "version", type: "integer", nullable: true)]
	private ?int $version;

	public function __construct() {
		$this->setId((int) null);
		$this->setVersion(1);
	}

	public function getId(): int {
		return $this->id;
	}

	public function setId(int $id): void {
		$this->id = $id;
	}

	public function getAddress(): ?string {
		return $this->address;
	}

	public function setAddress(?string $address): void {
		$this->address = $address;
	}

	public function getType(): ?int {
		return $this->type;
	}

	public function setType(?int $type): void {
		$this->type = $type;
	}

	public function getVersion(): ?int {
		return $this->version;
	}

	public function setVersion(?int $version): void {
		$this->version = $version;
	}
}