<?php
namespace App\Entity;

use App\Model\EmailType;
use App\Model\PhonenumberType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;

#[ORM\Entity(repositoryClass: "App\Repository\PlayerRepository")]
#[ORM\Table(name: "player")]
#[ORM\Index( columns: ["league_id"], name: "fk_player_league_id" )]
#[ORM\Index( columns: ["name_id"], name: "fk_player_name_id" )]
#[ORM\Index( columns: ["address_id"], name: "fk_player_address_id" )]
class PlayerDE {
	#[ORM\Id]
	#[ORM\Column(name: "id", type: "bigint", nullable: false)]
	#[ORM\GeneratedValue(strategy: "AUTO")]
	private int $id;

	#[ORM\OneToOne(targetEntity: "App\Entity\AddressDE", cascade: ["all"])]
	private ?AddressDE $address;

	#[ORM\Column(name: "defunct", type: "boolean", nullable: true, options: ["default" => 0])]
	private ?bool $defunct;

	#[ORM\ManyToMany( targetEntity: "App\Entity\EmailDE", cascade: ["all"], fetch: "EAGER", orphanRemoval: true )]
	#[ORM\JoinTable(name: "player_emails")]
	#[ORM\JoinColumn(name: "player_id", referencedColumnName: "id")]
	#[ORM\InverseJoinColumn(name: "email_id", referencedColumnName: "id", unique: true)]
	private PersistentCollection $emailAddresses;

	#[ORM\ManyToOne( targetEntity: "App\Entity\LeagueDE", cascade: ["refresh"], inversedBy: "players" )]
	private ?LeagueDE $league;

	#[ORM\OneToOne(targetEntity: "App\Entity\FullnameDE", cascade: ["all"])]
	private ?FullnameDE $name;

	#[ORM\ManyToMany( targetEntity: "App\Entity\PhonenumberDE", cascade: ["all"], fetch: "EAGER", orphanRemoval: true )]
	#[ORM\JoinTable(name: "player_phonenumbers")]
	#[ORM\JoinColumn(name: "player_id", referencedColumnName: "id")]
	#[ORM\InverseJoinColumn(name: "phonenumber_id", referencedColumnName: "id", unique: true)]
	private PersistentCollection $phonenumbers;

	#[ORM\Column(name: "seedhandicapindex", type: "float", precision: 10, scale: 0, nullable: true)]
	private ?float $seedhandicapindex;

	#[ORM\Column(name: "type", type: "string", length: 255, nullable: true)]
	private ?string $type;

	#[ORM\Version]
	#[ORM\Column(name: "version", type: "integer")]
	private ?int $version;

	public function __construct(EntityManagerInterface $em) {
		$this->setId((int) null);
		$this->setVersion(1);
		$this->setEmailAddresses(new PersistentCollection($em, new ClassMetadata('App\Entity\EmailDE'), new ArrayCollection()));
		$this->setPhonenumbers(new PersistentCollection($em, new ClassMetadata('App\Entity\PhonenumberDE'), new ArrayCollection()));
	}

	public function getAddress(): ?AddressDE {
		return $this->address;
	}

	public function getCellphonenumber(): ?string {
		return $this->getPhoneNumber(PhonenumberType::CELL);
	}

	public function getEmailAddresses(): PersistentCollection {
		return $this->emailAddresses;
	}

	private function getEmailAddress($type): ?string {
		if (!empty($this->emailAddresses)) {
			foreach($this->emailAddresses as $emailAddress) {
				if ($emailAddress->getType() == EmailType::toOrdinal($type)) {
					return $emailAddress->getAddress();
				}
			}
		}
		return null;
	}

	public function getGeneration(): ?string {
		return $this->name->getGeneration();
	}

	public function getId(): int {
		return $this->id;
	}

	public function getLeague(): ?LeagueDE {
		return $this->league;
	}

	public function getFirstname(): ?string {
		return $this->name->getFirstname();
	}

	public function getHomephonenumber(): ?string {
		return $this->getPhoneNumber(PhonenumberType::HOME);
	}

	public function getLastname(): ?string {
		return $this->name->getLastname();
	}

	public function getMiddlenameorinitial(): ?string {
		return $this->name->getMiddlenameorinitial();
	}

	public function getName(): ?FullnameDE {
		return $this->name;
	}

	public function getOtheremailaddress(): ?string {
		return $this->getEmailAddress(EmailType::OTHER);
	}

	public function getOtherphonenumber(): ?string {
		return $this->getPhoneNumber(PhonenumberType::OTHER);
	}

	public function getPersonalemailaddress(): ?string {
		return $this->getEmailAddress(EmailType::PERSONAL);
	}

	public function getPhonenumbers(): PersistentCollection {
		return $this->phonenumbers;
	}

	private function getPhoneNumber($type): ?string {
		if (!empty($this->phonenumbers)) {
			foreach($this->phonenumbers as $phoneNumber) {
				if ($phoneNumber->getType() == PhonenumberType::toOrdinal($type)) {
					return $phoneNumber->getNumber();
				}
			}
		}
		return null;
	}

	public function getSeedhandicapindex(): ?float {
		return $this->seedhandicapindex;
	}

	public function getType(): ?string {
		return $this->type;
	}

	public function getVersion(): ?int {
		return $this->version;
	}

	public function getWorkemailaddress(): ?string {
		return $this->getEmailAddress(EmailType::WORK);
	}

	public function getWorkphonenumber(): ?string {
		return $this->getPhoneNumber(PhonenumberType::WORK);
	}

	public function isDefunct(): ?bool {
		return $this->defunct;
	}

	public function setAddress(?AddressDE $address): void {
		$this->address = $address;
	}

	public function setCellphonenumber(?string $number): void {
		$this->setPhoneNumber($number, PhonenumberType::CELL);
	}

	public function setDefunct(?bool $defunct): void {
		$this->defunct = $defunct;
	}

	public function setEmailAddresses(PersistentCollection $emailAddresses): void {
		$this->emailAddresses = $emailAddresses;
	}

	private function setEmailAddress(?string $address, $type): void {
		if (!empty($this->emailAddresses)) {
			foreach($this->emailAddresses as $emailAddress) {
				if ($emailAddress->getType() == EmailType::toOrdinal($type)) {
					if (is_null($address)) {
						$this->emailAddresses->removeElement($emailAddress);
					} else {
						$emailAddress->setAddress($address);
					}
					return;
				}
			}
		}
		if (!is_null($address)) {
			$emailAddress = new EmailDE();
			$emailAddress->setAddress($address);
			$emailAddress->setType(EmailType::toOrdinal($type));
			$this->emailAddresses[] = $emailAddress;
		}
	}

	public function setGeneration(?string $generation): void {
		$this->name->setGeneration($generation);
	}

	public function setId(int $id): void {
		$this->id = $id;
	}

	public function setFirstname(?string $firstname): void {
		$this->name->setFirstname($firstname);
	}

	public function setHomephonenumber(?string $number): void {
		$this->setPhoneNumber($number, PhonenumberType::HOME);
	}

	public function setLastname(?string $lastname): void {
		$this->name->setLastname($lastname);
	}

	public function setLeague(?LeagueDE $league): void {
		$this->league = $league;
	}

	public function setMiddlenameorinitial(?string $middlenameorinitial): void {
		$this->name->setMiddlenameorinitial($middlenameorinitial);
	}

	public function setName(?FullnameDE $name): void {
		$this->name = $name;
	}

	public function setOtheremailaddress(?string $address): void {
		$this->setEmailAddress($address, EmailType::OTHER);
	}

	public function setOtherphonenumber(?string $number): void {
		$this->setPhoneNumber($number, PhonenumberType::OTHER);
	}

	public function setPersonalemailaddress(?string $address): void {
		$this->setEmailAddress($address, EmailType::PERSONAL);
	}

	public function setPhonenumbers(PersistentCollection $phonenumbers): void {
		$this->phonenumbers = $phonenumbers;
	}

	private function setPhoneNumber(?string $number, $type): void {
		if (!empty($this->phonenumbers)) {
			foreach($this->phonenumbers as $phoneNumber) {
				if ($phoneNumber->getType() == PhonenumberType::toOrdinal($type)) {
					if (is_null($number)) {
						$this->phonenumbers->removeElement($phoneNumber);
					} else {
						$phoneNumber->setNumber($number);
					}
					return;
				}
			}
		}
		if (!is_null($number)) {
			$phoneNumber = new PhonenumberDE();
			$phoneNumber->setNumber($number);
			$phoneNumber->setType(PhonenumberType::toOrdinal($type));
			$this->phonenumbers[] = $phoneNumber;
		}
	}

	public function setSeedhandicapindex(?float $seedhandicapindex): void {
		$this->seedhandicapindex = $seedhandicapindex;
	}

	public function setType(?string $type): void {
		$this->type = $type;
	}

	public function setVersion(?int $version): void {
		$this->version = $version;
	}

	public function setWorkemailaddress(?string $address): void {
		$this->setEmailAddress($address, EmailType::WORK);
	}

	public function setWorkphonenumber(?string $number): void {
		$this->setPhoneNumber($number, PhonenumberType::WORK);
	}
}