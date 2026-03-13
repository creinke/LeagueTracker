<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: "App\Repository\UserRepository")]
#[ORM\Table(name: "user")]
class UserDE implements UserInterface, PasswordAuthenticatedUserInterface {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(name: "id", type: "bigint", nullable: false)]
	private ?int $id;

	#[ORM\ManyToOne( targetEntity: "App\Entity\LeagueDE", cascade: ["refresh"], inversedBy: "users" )]
	private LeagueDE $league;

	private string|null $leagueName;

	#[ORM\Column(type: "string")]
	private string|null $password;

	#[Assert\NotBlank]
	#[Assert\Length(max: 4096)]
	private string|null $plainPassword;

	#[ORM\Column(type: "json")]
	private array $roles = [];

	private string|null $roleList;

	#[ORM\Column(type: "string", length: 180, unique: true)]
	private string|null $username;

	#[ORM\Column(type: "string", length: 255, unique: true, nullable: true)]
	private ?string $apiToken = null;

	#[ORM\Version]
	#[ORM\Column(name: "version", type: "integer", nullable: true)]
	private ?int $version;

	public function __construct(?EntityManagerInterface $em) {
		$this->setId((int) null);
		$this->setVersion(1);
	}

	public function eraseCredentials(): void {
		// If you store any temporary, sensitive data on the user, clear it here
		// $this->plainPassword = null;
	}

	public function getId(): ?int {
		return $this->id;
	}

	public function getLeague(): LeagueDE {
		return $this->league;
	}

	public function getLeagueName(): string|null {
		return $this->leagueName;
	}

	public function getPassword(): string|null {
		return (string) $this->password;
	}

	public function getPlainPassword(): string|null {
		return $this->plainPassword;
	}

	public function getRoles(): array {
		$roles = $this->roles;
		$roles[] = 'ROLE_USER';
		return array_unique($roles);
	}

	public function getRoleList(): string|null {
		return $this->roleList;
	}

	public function getUsername(): string|null {
		return (string) $this->username;
	}

	public function getUserIdentifier(): string {
		return (string) $this->username;
	}

	public function getApiToken(): ?string {
		return $this->apiToken;
	}

	public function setApiToken(?string $apiToken): self {
		$this->apiToken = $apiToken;
		return $this;
	}

	public function getVersion(): ?int {
		return $this->version;
	}

	private function setId(int $id): void {
		$this->id = $id;
	}

	public function setLeague(LeagueDE $league): void {
		$this->league = $league;
	}

	public function setLeagueName(string $leagueName): void {
		$this->leagueName = $leagueName;
	}

	public function setPassword(string $password): self {
		$this->password = $password;
		return $this;
	}

	public function setPlainPassword(string|null $plainPassword): void {
		$this->plainPassword = $plainPassword;
	}

	public function setRoles(array $roles): self {
		$this->roles = $roles;
		return $this;
	}

	public function setRoleList(string $roleList): void {
		$this->roleList = $roleList;
	}

	public function setUsername(string $username): self {
		$this->username = $username;
		return $this;
	}

	public function setVersion(?int $version): void {
		$this->version = $version;
	}
}