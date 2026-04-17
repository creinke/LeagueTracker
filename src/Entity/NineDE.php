<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity("App\Entity\NineDE")]
#[ORM\Table(name: "nine")]
#[ORM\Index( name: "fk_nine_course_id", columns: ["course_id"])]
class NineDE {
    #[ORM\ManyToOne( targetEntity: "App\Entity\CourseDE", cascade: ["refresh"], inversedBy: "nines" )]
    private CourseDE $course;

    #[ORM\Id]
    #[ORM\Column(name: "id", type: "bigint", nullable: false)]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    private int $id;

    #[ORM\Column(name: "name", type: "string", length: 255, nullable: true)]
    private ?string $name;

    #[ORM\OneToMany( targetEntity: "App\Entity\TeeDE", mappedBy: "nine", cascade: ["all"], fetch: "EAGER")]
    private Collection $tees;

    #[ORM\Version]
    #[ORM\Column(name: "version", type: "integer", nullable: true)]
    private ?int $version;

    public function __construct() {
        $this->id = 0;
        $this->version = 1;
        $this->tees = new ArrayCollection();
    }

    public function findTeeByName(string $name): ?TeeDE {
        foreach ($this->tees as $tee) {
            if ($tee->getName() === $name) {
                return $tee;
            }
        }
        return null;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function getTees(): Collection {
        return $this->tees;
    }

    public function getVersion(): ?int {
        return $this->version;
    }

    public function getCourse(): CourseDE {
        return $this->course;
    }

    public function setCourse(CourseDE $course): void {
        $this->course = $course;
    }

    public function setId(int $id): void {
        $this->id = $id;
    }

    public function setName(?string $name): void {
        $this->name = $name;
    }

    public function setTees(Collection $tees): void {
        $this->tees = $tees;
    }

    public function setVersion(?int $version): void {
        $this->version = $version;
    }
}