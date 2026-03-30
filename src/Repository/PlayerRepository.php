<?php
namespace App\Repository;

define('LEAGUE_ENTITY', 'App\Entity\LeagueDE');
define('NAME_ENTITY', 'App\Entity\FullnameDE');
define('PLAYER_ENTITY', 'App\Entity\PlayerDE');

use App\Entity\AddressDE;
use App\Entity\EmailDE;
use App\Entity\FullnameDE;
use App\Entity\LeagueDE;
use App\Entity\PhonenumberDE;
use App\Entity\PlayerDE;
use App\Model\EmailType;
use App\Model\PhonenumberType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Class where you can add "persist" and other specialized methods associated with the player table.
 */
class PlayerRepository extends AbstractBaseRepository {

	public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
		parent::__construct($em, $logger, PlayerDE::class);
	}

	/**
	 * Checks to make sure all address-required fields are set
	 * Performs secondary filtering/sanitization of data
	 *
	 * @param array $addressData
	 */
    protected function checkAddressData(array &$addressData): void {
        $addressData['addressline1'] ??= '';
        $addressData['addressline2'] ??= '';
        $addressData['city'] ??= '';
        $addressData['postalcode'] ??= '';
        $addressData['state'] ??= '';
        $addressData['country'] ??= '';
    }

	/**
	 * Checks to make sure all email-required fields are set
	 * This is also where to perform secondary filtering/sanitization of data
	 *
	 * @param array $emailData
	 */
    protected function checkEmailData(array &$emailData): void {
        $emailData['address'] ??= '';
        $emailData['type'] ??= '';
    }

    /**
     * Checks to make sure all the emails required fields in the collection are set.
     * This is also where to perform secondary filtering/sanitization of data
     *
     * @param array $emailsData
     */
    protected function checkEmailsData(array &$emailsData): void {
        for ($i = 0; $i < sizeof($emailsData); $i++) {
            $this->checkEmailData($emailsData[$i]);
        }
    }

	/**
	 * Checks to make sure all name-required fields are set
	 * This is also where to perform secondary filtering/sanitization of data
	 *
	 * @param string $name
	 *
	 * @return array
	 * @throws Exception
	 */
    public function checkName(string $name): array {
        $nameArray = explode(" ", $name);
        $nameData = array();

        if (sizeof($nameArray) == 0 || sizeof($nameArray) > 4) {
	        $this->logError('Incorrect number of strings.  Unable to parse name', [ 'player name' => $name, ]);
	        throw new Exception('Incorrect number of strings.  Unable to parse player name: ' . $name, 0, null);
        }
        $nameData['firstName'] = $nameArray[0];

        if (sizeof($nameArray) == 2) {
            $nameData['lastName'] = $nameArray[1];
            $nameData['middleNameOrInitial'] = '';
            $nameData['generation'] = '';
        } else if (sizeof($nameArray) == 3) {
            $nameData['middleNameOrInitial'] = $nameArray[1];
            $nameData['lastName'] = $nameArray[2];
            $nameData['generation'] = '';
        } else {
            $nameData['middleNameOrInitial'] = $nameArray[1];
            $nameData['lastName'] = $nameArray[2];
            $nameData['generation'] = $nameArray[2];
        }
        return $nameData;
    }


    /**
     * Checks to make sure all name-required fields are set
     * This is also where to perform secondary filtering/sanitization of data
     *
     * @param array $nameData
     */
    protected function checkNameData(array &$nameData): void {
        $nameData['firstName'] ??= '';
        $nameData['lastName'] ??= '';
        $nameData['middleNameOrInitial'] ??= '';
        $nameData['generation'] ??= '';
    }

    /**
     * Checks to make sure all phonenumber required fields are set
     * This is also where to perform secondary filtering/sanitization of data
     *
     * @param array $phonenumberData
     */
    protected function checkPhonenumberData(array &$phonenumberData): void {
        $phonenumberData['number'] ??= '';
        $phonenumberData['type'] ??= '';
    }

    /**
     * Checks to make sure all the phonenumbers required fields in the collection are set.
     * This is also where to perform secondary filtering/sanitization of data
     *
     * @param array $phonenumbersData
     */
    protected function checkPhonenumbersData(array &$phonenumbersData): void {
        for ($i = 0; $i < sizeof($phonenumbersData); $i++) {
            $this->checkPhonenumberData($phonenumbersData[$i]);
        }
    }

    /**
     * Checks to make sure all player-required fields are set
     * This is also where to perform secondary filtering/sanitization of data
     *
     * @param array $playerData
     */
    protected function checkPlayerData(array &$playerData): void {
        $playerData['defunct'] ??= 'false';
        // $playerData['playerNumber'] ??= '';
        $playerData['seedHandicapIndex'] ??= '';
        $playerData['seedScores'] ??= '';
        $playerData['type'] ??= '';

        $this->checkNameData($playerData["name"]);

        if (isset($playerData['address'])) {
            $this->checkAddressData($playerData["address"]);
        } else {
            $playerData["address"] = NULL;
        }
        if (isset($playerData['email'])) {
            $this->checkEmailsData($playerData["email"]);
        } else {
            $playerData["email"] = NULL;
        }
        if (isset($playerData['phoneNumber'])) {
            $this->checkPhonenumbersData($playerData["phoneNumber"]);
        } else {
            $playerData["phonenumber"] = NULL;
        }
    }

    /**
     * @param int $id of player
     *
     * @return ?PlayerDE
     */
    public function findById(int $id): ?PlayerDE {
        return $this->findOneBy(array('id' => $id));
    }

    /**
     * Returns all the players and names of players in the specified league. 
     * 
     * @param int $leagueId of league
     *
     * @return mixed|Statement|array|NULL
     * @throws Exception
     */
    public function findAllPlayers(int $leagueId): mixed {
        try {
            // get QB instance
            $qb = $this->createQueryBuilder('player');

            $expr = $qb->expr()->eq('player.league', '?1');
            $qb->setParameters(array(1 => $leagueId));

            // Implement join
            $qb->addSelect('name')    // Adds Name entities to result set
                ->join(NAME_ENTITY, 'name', Join::WITH, 'player.name = name.id')
                ->where($expr)
                ->orderBy('name.lastname', 'ASC')
                ->addOrderBy('name.firstname', 'ASC');

            $queryResult = $qb->getQuery()->getResult();
            $this->logDebug($qb->getQuery()->getSql(), ['queryResult' => $queryResult]);
            return $queryResult;
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method for league Id [%s]: %s',
		        'PlayerRepository::findAllPlayers', $leagueId, $e->getMessage()));
	        throw $e;
        }
    }

    /**
     * @param int $leagueId league id
     * @param array $nameData name parameters
     *
     * @return array|mixed|Statement|NULL
     * @throws Exception
     */
    public function findPlayerByName(int $leagueId, array $nameData): mixed {
        try {
            // get QB instance
            $qb = $this->createQueryBuilder('player');

            $expr = $qb->expr()->andX(
                $qb->expr()->eq('player.league', '?1'),
                $qb->expr()->like('name.firstname', '?2'),
                $qb->expr()->like('name.lastname', '?3'),
                $qb->expr()->like('name.middlenameorinitial', '?4'),
                $qb->expr()->like('name.generation', '?5'));

            $qb->setParameters(array(1 => $leagueId, 2 => $nameData['firstName'], 3 => $nameData['lastName'], 4 => $nameData['middleNameOrInitial'], 5 => $nameData['generation']));

            // Implement join
            $qb->addSelect('name')    // Adds Name entities to result set
                ->join(NAME_ENTITY, 'name', Join::WITH, 'player.name = name.id')
                ->where($expr);

            $queryResult = $qb->getQuery()->getResult();
	        $this->logDebug($qb->getQuery()->getSql(), ['queryResult' => $queryResult]);
	        return $queryResult;
        } catch (Exception $e) {
			$playerName = $nameData['firstName'] . " " . $nameData['lastName'] . " " . $nameData['middleNameOrInitial'] . " " . $nameData['generation'];
	        $this->logError(sprintf('Error in the %s method for league Id, player name [%s, %s]: %s',
		        'PlayerRepository::findPlayerByName', $leagueId, $playerName, $e->getMessage()));
	        throw $e;
        }
    }

	/**
	 * @param int $leagueId
	 * @param string $name
	 *
	 * @return array|mixed|Statement|NULL
	 * @throws Exception
	 */
    public function findPlayerByNameString(int $leagueId, string $name): mixed {
        $nameData = $this->checkName($name);
        return $this->findPlayerByName($leagueId, $nameData);
    }

	/**
	 * Deletes a player entity
	 *
	 * @param PlayerDE $player
	 *
	 * @return PlayerDE
	 * @throws Exception
	 */
    public function removePlayer(PlayerDE $player): PlayerDE {
        try {
            $this->getEntityManager()->remove($player);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method for player [%s]: %s',
		        'PlayerRepository::removePlayer', $player->getName()->getFullname(), $e->getMessage()));
	        throw $e;
        }
        return $player;
    }

	/**
	 * Adds or updates player entity
	 *
	 * @param array $playerData new or modified player data
	 * @param LeagueDE $league
	 * @param PlayerDE|null $player
	 *
	 * @return ?PlayerDE
	 * @throws Exception
	 */
    public function save(array $playerData, LeagueDE $league, ?PlayerDE $player = NULL): ?PlayerDE {
        $this->checkPlayerData($playerData);
        $player = $this->setPlayerData($playerData, $league, $player);

        try {
            $this->getEntityManager()->persist($player);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method for player [%s]: %s',
		        'PlayerRepository::save', $player->getName()->getFullname(), $e->getMessage()));
	        throw $e;
        }
        return $player;
    }

	/**
	 * Adds all player entities
	 *
	 * @param array $playersData new or modified list of player data
	 * @param LeagueDE $league
	 *
	 * @return PersistentCollection
	 * @throws Exception
	 */
    public function saveAll(array $playersData, LeagueDE $league): PersistentCollection {
        $players = new PersistentCollection($this->getEntityManager(), new ClassMetadata('App\Entity\PlayerDE'), new ArrayCollection());

        foreach($playersData as $playerData) {
            $player = $this->save($playerData, $league);
            $players->add($player);
        }
        return $players;
    }

	/**
	 * Adds or updates player entity
	 *
	 * @param PlayerDE $player
	 *
	 * @return PlayerDE
	 * @throws Exception
	 */
    public function savePlayer(PlayerDE $player): PlayerDE {
        try {
            $this->getEntityManager()->persist($player);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method for player [%s]: %s',
		        'PlayerRepository::savePlayer', $player->getName()->getFullname(), $e->getMessage()));
	        throw $e;
        }
        return $player;
    }

	/**
	 * Calls setters to assign $addressData to properties in $address
	 *
	 * @param array $addressData
	 * @param ?AddressDE|null $address
	 *
	 * @return AddressDE $address
	 */
    protected function setAddressData(array $addressData, ?AddressDE $address = NULL): AddressDE {
        if (!$address) {
            $address = new AddressDE();
        }
        $address->setAddressline1($addressData['addressline1']);
        $address->setAddressline2('addressline2');
        $address->setCity($addressData['city']);
        $address->setPostalcode($addressData['postalcode']);

        $regionRepository = new RegionRepository($this->getEntityManager(), $this->getLogger());
        $region = $regionRepository->findOneBy(array('code' => $addressData['state']));
        $address->setRegion($region);

        return $address;
    }

	/**
	 * Calls setters to assign $emailData to properties in $email
	 *
	 * @param array $emailData
	 * @param ?EmailDE|null $email
	 *
	 * @return EmailDE $email
	 */
    protected function setEmailData(array $emailData, ?EmailDE $email = NULL): EmailDE {
        $email ??= new EmailDE();

        $email->setAddress($emailData['address']);
        $email->setType(EmailType::toOrdinal($emailData['type']));

        return $email;
    }

	/**
	 * @param array $emailsData array of email data
	 * @param ?PersistentCollection|null $emails of EmailDE
	 *
	 * @return PersistentCollection of EmailDE
	 */
    protected function setEmailsData(array $emailsData, ?PersistentCollection $emails = NULL): PersistentCollection {
        $emails ??= new PersistentCollection($this->getEntityManager(), new ClassMetadata('App\Entity\EmailDE'), new ArrayCollection());

        foreach($emailsData as $emailData) {
            $emails[] = $this->setEmailData($emailData);
        }
        return $emails;
    }

	/**
	 * Calls setters to assign $nameData to properties in $name
	 *
	 * @param array $nameData
	 * @param ?FullnameDE|null $name
	 *
	 * @return FullnameDE $name
	 */
    protected function setNameData(array $nameData, ?FullnameDE $name = NULL): FullnameDE {
        $name ??= new FullnameDE();

        $name->setFirstname($nameData['firstName']);
        $name->setLastname($nameData['lastName']);
        $name->setMiddlenameorinitial($nameData['middleNameOrInitial']);
        $name->setGeneration($nameData['generation']);

        return $name;
    }

	/**
	 * Calls setters to assign $phonenumberData to properties in $phonenumber
	 *
	 * @param array $phonenumberData
	 * @param ?PhonenumberDE|null $phonenumber
	 *
	 * @return PhonenumberDE $phonenumber
	 */
    protected function setPhonenumberData(array $phonenumberData, ?PhonenumberDE $phonenumber = NULL): PhonenumberDE {
        $phonenumber ??= new PhonenumberDE();

        $phonenumber->setNumber($phonenumberData['number']);
        $phonenumber->setType(PhonenumberType::toOrdinal($phonenumberData['type']));

        return $phonenumber;
    }

	/**
	 * @param array $phonenumbersData array of phonenumber data
	 * @param ?PersistentCollection|null $phonenumbers of Entity\PhonenumberDE
	 *
	 * @return PersistentCollection of Entity\PhonenumberDE
	 */
    protected function setPhonenumbersData(array $phonenumbersData, ?PersistentCollection $phonenumbers = NULL): PersistentCollection {
        $phonenumbers ??= new PersistentCollection($this->getEntityManager(), new ClassMetadata('App\Entity\PhonenumberDE'), new ArrayCollection());

        foreach($phonenumbersData as $phonenumberData) {
            $phonenumbers[] = $this->setPhonenumberData($phonenumberData);
        }
        return $phonenumbers;
    }

    /**
     * Calls setters to assign $playerData to properties in $player
     *
     * @param array $playerData
     * @param LeagueDE $league
     * @param ?PlayerDE $player
     * @return PlayerDE $player
     */
    protected function setPlayerData(array $playerData, LeagueDE $league, ?PlayerDE $player = NULL): PlayerDE {
        $player ??= new PlayerDE($this->getEntityManager());

        $player->setDefunct($playerData['defunct'] == "true" ? true : false);
        $player->setName($this->setNameData($playerData['name']));
        // $player->setPlayernumber($playerData['playerNumber']);
        $player->setSeedhandicapindex($playerData['seedHandicapIndex']);
        $player->setType($playerData['type']);

        if (!empty($playerData['addresss'])) {
            $player->setAddress($this->setAddressData($playerData['addresss']));
        }
        if (!empty($playerData['email'])) {
            $player->setEmailAddresses($this->setEmailsData($playerData['email']));
        }
        if (!empty($playerData['phoneNumber'])) {
            $player->setPhonenumbers($this->setPhonenumbersData($playerData['phoneNumber']));
        }
        $player->setLeague($league);

        return $player;
    }
}