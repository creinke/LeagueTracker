<?php
namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity("App\Entity\PaymentDE")]
#[ORM\Table(name: "payment")]
#[ORM\Index( columns: ["player_id"], name: "fk_payment_player_id" )]
class PaymentDE {
	#[ORM\Column(name: "carryoveramount", type: "float", precision: 10, scale: 2, nullable: true)]
	private ?float $carryoveramount;

	#[ORM\Column(name: "carryovertimestamp", type: "datetime", nullable: true)]
	private ?DateTime $carryovertimestamp;

	#[ORM\Id]
	#[ORM\Column(name: "id", type: "bigint", nullable: false)]
	#[ORM\GeneratedValue(strategy: "AUTO")]
	private int $id;

	#[ORM\Column(name: "paymentamount", type: "float", precision: 10, scale: 2, nullable: true)]
	private ?float $paymentamount;

	#[ORM\Column(name: "paymenttimestamp", type: "datetime", nullable: true)]
	private ?DateTime $paymenttimestamp;

	#[ORM\OneToOne(targetEntity: "App\Entity\PlayerDE", cascade: ["refresh"])]
	private ?PlayerDE $player;

	#[ORM\Version]
	#[ORM\Column(name: "version", type: "integer", nullable: true)]
	private ?int $version;

	public function __construct() {
		$this->setId((int) null);
		$this->setVersion(1);
	}

	public function getCarryoveramount(): ?float {
		return $this->carryoveramount;
	}

	public function getCarryovertimestamp(): ?DateTime {
		return $this->carryovertimestamp;
	}

	public function getId(): int {
		return $this->id;
	}

	public function getPaymentamount(): ?float {
		return $this->paymentamount;
	}

	public function getPaymenttimestamp(): ?DateTime {
		return $this->paymenttimestamp;
	}

	public function getPlayer(): ?PlayerDE {
		return $this->player;
	}

	public function getVersion(): ?int {
		return $this->version;
	}

	public function setCarryoveramount(?float $carryoveramount): void {
		$this->carryoveramount = $carryoveramount;
	}

	public function setCarryovertimestamp(?DateTime $carryovertimestamp): void {
		$this->carryovertimestamp = $carryovertimestamp;
	}

	public function setId(int $id): void {
		$this->id = $id;
	}

	public function setPaymentamount(?float $paymentamount): void {
		$this->paymentamount = $paymentamount;
	}

	public function setPaymenttimestamp(?DateTime $paymenttimestamp): void {
		$this->paymenttimestamp = $paymenttimestamp;
	}

	public function setPlayer(?PlayerDE $player): void {
		$this->player = $player;
	}

	public function setVersion(?int $version): void {
		$this->version = $version;
	}
}