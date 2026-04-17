<?php
namespace App\Entity;

use App\Repository\RegionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RegionRepository::class)]

#[ORM\Table(name: "region")]
#[ORM\Index( name: "fk_region_country_id", columns: ["country_id"])]
class RegionDE {
    #[ORM\Id]
    #[ORM\Column(name: "id", type: "bigint", length: 20)]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    private int $id;

    #[ORM\Column(name: "code", type: "string", length: 2, nullable: false)]
    private string $code;

    #[ORM\Column(name: "name", type: "string", length: 40, nullable: false)]
    private string $name;

    #[ORM\ManyToOne( targetEntity: "App\Entity\CountryDE", cascade: ["refresh"], inversedBy: "regions" )]
    private CountryDE $country;

    private ?string $countryName;

    #[ORM\Version]
    #[ORM\Column(name: "version", type: "integer", length: 11, nullable: false)]
    private int $version;

    public function __construct() {
        $this->id = 0;
        $this->version = 1;
    }

    /** @noinspection PhpUnused */
    public function getCountryName(): ?string {
        return $this->countryName;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getCode(): string {
        return $this->code;
    }

    public function getCountry(): CountryDE {
        return $this->country;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getVersion(): int {
        return $this->version;
    }

    public function setCountryName(?string $countryName): void {
        $this->countryName = $countryName;
    }

    public function setId(int $id): void {
        $this->id = $id;
    }

    public function setCode(string $code): void {
        $this->code = $code;
    }

    public function setCountry(CountryDE $country): void {
        $this->country = $country;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function setVersion(int $version): void {
        $this->version = $version;
    }
}