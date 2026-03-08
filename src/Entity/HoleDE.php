<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity("App\Entity\HoleDE")]
#[ORM\Table(name: "hole")]
#[ORM\Index( columns: ["tee_id"], name: "fk_hole_tee_id" )]
class HoleDE {
	#[ORM\Id]
	#[ORM\Column(name: "id", type: "bigint", nullable: false)]
	#[ORM\GeneratedValue(strategy: "AUTO")]
	private int $id;

	#[ORM\Column(name: "handicap", type: "integer", nullable: true)]
	private ?int $handicap;

	#[ORM\Column(name: "holenumber", type: "integer", nullable: true)]
	private ?int $holenumber;

	#[ORM\Column(name: "length", type: "integer", nullable: true)]
	private ?int $length;

	#[ORM\Column(name: "name", type: "string", length: 255, nullable: true)]
	private ?string $name;

	#[ORM\Column(name: "par", type: "integer", nullable: true)]
	private ?int $par;

	#[ORM\ManyToOne( targetEntity: "App\Entity\TeeDE", cascade: ["refresh"], inversedBy: "holes" )]
	private ?TeeDE $tee;

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

	public function getHandicap(): ?int {
		return $this->handicap;
	}

	public function setHandicap(?int $handicap): void {
		$this->handicap = $handicap;
	}

	public function getHolenumber(): ?int {
		return $this->holenumber;
	}

	public function setHolenumber(?int $holenumber): void {
		$this->holenumber = $holenumber;
	}

	public function getLength(): ?int {
		return $this->length;
	}

	public function setLength(?int $length): void {
		$this->length = $length;
	}

	public function getName(): ?string {
		return $this->name;
	}

	public function setName(?string $name): void {
		$this->name = $name;
	}

	public function getPar(): ?int {
		return $this->par;
	}

	public function setPar(?int $par): void {
		$this->par = $par;
	}

	public function getTee(): ?TeeDE {
		return $this->tee;
	}

	public function setTee(?TeeDE $tee): void {
		$this->tee = $tee;
	}

	public function getVersion(): ?int {
		return $this->version;
	}

	public function setVersion(?int $version): void {
		$this->version = $version;
	}
}