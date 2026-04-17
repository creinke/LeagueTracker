<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity("App\Entity\FullnameDE")]
#[ORM\Table(name: "fullname")]
class FullnameDE {
    #[ORM\Id]
    #[ORM\Column(name: "id", type: "bigint", nullable: false)]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    private int $id;

    #[ORM\Column(name: "firstName", type: "string", length: 255, nullable: true)]
    private ?string $firstname;

    #[ORM\Column(name: "generation", type: "string", length: 255, nullable: true)]
    private ?string $generation;

    #[ORM\Column(name: "lastName", type: "string", length: 255, nullable: true)]
    private ?string $lastname;

    #[ORM\Column(name: "middleNameOrInitial", type: "string", length: 255, nullable: true)]
    private ?string $middlenameorinitial;

    #[ORM\Version]
    #[ORM\Column(name: "version", type: "integer", nullable: true)]
    private ?int $version;

    public function __construct() {
        $this->id = 0;
        $this->firstname = null;
        $this->generation = null;
        $this->lastname = null;
        $this->middlenameorinitial = null;
        $this->version = 1;
    }

    public function getId(): int {
        return $this->id;
    }

    public function setId(int $id): void {
        $this->id = $id;
    }

    public function getFirstname(): ?string {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): void {
        $this->firstname = $firstname;
    }

    public function getGeneration(): ?string {
        return $this->generation;
    }

    public function setGeneration(?string $generation): void {
        $this->generation = $generation;
    }

    public function getLastname(): ?string {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): void {
        $this->lastname = $lastname;
    }

    public function getMiddlenameorinitial(): ?string {
        return $this->middlenameorinitial;
    }

    public function setMiddlenameorinitial(?string $middlenameorinitial): void {
        $this->middlenameorinitial = $middlenameorinitial;
    }

    public function getFullname(): string {
        $fullName = $this->getFirstname();

        if ($this->getMiddlenameorinitial() != NULL) {
            $fullName .= ' ' . $this->getMiddlenameorinitial();
        }
        $fullName .= ' ' . $this->getLastname();
        if ($this->getGeneration() != NULL) {
            $fullName .= ' ' . $this->getGeneration();
        }
        return $fullName;
    }

    public function setFullname(string $name): void {
        $nameArray = explode(" ", $name);

        $this->setFirstname($nameArray[0]);

        if (sizeof($nameArray) == 2) {
            $this->setLastname($nameArray[1]);
            $this->setMiddlenameorinitial('');
            $this->setGeneration('');
        } else if (sizeof($nameArray) == 3) {
            $this->setMiddlenameorinitial($nameArray[1]);
            $this->setLastname($nameArray[2]);
            $this->setGeneration('');
        } else {
            $this->setMiddlenameorinitial($nameArray[1]);
            $this->setLastname($nameArray[2]);
            $this->setGeneration($nameArray[3]);
        }
    }

    public function getVersion(): ?int {
        return $this->version;
    }

    public function setVersion(?int $version): void {
        $this->version = $version;
    }
}