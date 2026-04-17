<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity("App\Entity\PhonenumberDE")]
#[ORM\Table(name: "phonenumber")]
class PhonenumberDE {
    #[ORM\Id]
    #[ORM\Column(name: "id", type: "bigint", nullable: false)]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    private int $id;

    #[ORM\Column(name: "number", type: "string", length: 255, nullable: true)]
    private ?string $number;

    #[ORM\Column(name: "type", type: "integer", nullable: true)]
    private ?int $type;

    #[ORM\Version]
    #[ORM\Column(name: "version", type: "integer", nullable: true)]
    private ?int $version;

    public function __construct() {
        $this->id = 0;
        $this->version = 1;
    }

    public function getId(): int {
        return $this->id;
    }

    public function setId(int $id): void {
        $this->id = $id;
    }

    /** @noinspection PhpUnused */
    public function getNumber(): ?string {
        return $this->number;
    }

    public function setNumber(?string $number): void {
        $this->number = $number;
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