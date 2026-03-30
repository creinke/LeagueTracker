<?php
namespace App\Repository;

use App\Entity\LeagueDE;
use App\Entity\UserDE;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Class where you can add "persist" and other specialized methods associated with the user table.
 */
class UserRepository extends AbstractBaseRepository {

	private UserPasswordHasherInterface $passwordHasher;

	public function __construct(EntityManagerInterface $em, LoggerInterface $logger, UserPasswordHasherInterface $passwordHasher) {
		parent::__construct($em, $logger, UserDE::class);
		$this->passwordHasher = $passwordHasher;
	}

    /**
     * Checks to make sure all user-required fields are set
     * This is also where to perform secondary filtering/sanitization of data
     *
     * @param array $userData
     */
    protected function checkUserData(array &$userData): void {
        $userData['username'] ??= '';
        $userData['password'] ??= '';
        $userData['roles'] ??= '';
    }

    /**
     * @param int $id of user
     *
     * @return ?UserDE
     */
    public function findById(int $id): ?UserDE {
        return $this->findOneBy(array('id' => $id));
    }

    /**
     * @param string $userName of user
     *
     * @return ?UserDE
     */
    public function findUserByUserName( string $userName): ?UserDE {
        return $this->findOneBy(array('username' => $userName));
    }

	/**
	 * Deletes a user entity
	 *
	 * @param UserDE $user
	 *
	 * @return UserDE
	 * @throws Exception
	 */
    public function removeUser(UserDE $user): UserDE {
        try {
            $this->getEntityManager()->remove($user);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method for User [%s]: %s',
		        'UserRepository::removeUser', $user->getUsername(), $e->getMessage()));
            throw $e;
        }
        return $user;
    }

	/**
	 * Adds or updates user entity
	 *
	 * @param array $userData new or modified user data
	 * @param LeagueDE $league
	 * @param ?UserDE $user
	 *
	 * @return UserDE
	 * @throws Exception
	 */
    public function save(array $userData, LeagueDE $league, ?UserDE $user = NULL): UserDE {
        $this->checkUserData($userData);
        $user = $this->setUserData($userData, $league, $user);

        try {
            $this->getEntityManager()->persist($user);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method for User [%s]: %s',
		        'UserRepository::save', $user->getUsername(), $e->getMessage()));
            throw $e;
        }
        return $user;
    }

	/**
	 * Adds all user entities
	 *
	 * @param array $usersData new or modified list of user data
	 * @param LeagueDE $league
	 *
	 * @return PersistentCollection of PlayerDE
	 * @throws Exception
	 */
    public function saveAll(array $usersData, LeagueDE $league): PersistentCollection {
        $users = new PersistentCollection($this->getEntityManager(), new ClassMetadata('App\Entity\UserDE'), new ArrayCollection());

        foreach($usersData as $userData) {
            $user = $this->save($userData, $league);
            $users->add($user);
        }
        return $users;
    }

    /**
     * @param UserDE $user
     *
     * @return UserDE
     * @throws Exception
     */
    public function saveUser(UserDE $user): UserDE {
        try {
            $this->getEntityManager()->persist($user);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method for User [%s]: %s',
		        'UserRepository::saveUser', $user->getUsername(), $e->getMessage()));
            throw $e;
        }
        return $user;
    }

	/**
	 * Calls setters to assign $userData to properties in $course
	 *
	 * @param array $userData
	 * @param LeagueDE $league
	 * @param ?UserDE $user
	 *
	 * @return UserDE $user
	 */
    protected function setUserData(array $userData, LeagueDE $league, ?UserDE $user = NULL): UserDE {
	    $user ??= new UserDE($this->getEntityManager());

        $user->setUsername($userData['username']);
	    $hashedPassword = $this->passwordHasher->hashPassword($user, $userData['password']);
	    $user->setPassword($hashedPassword);
        $user->setLeague($league);

        $roles = array();
        $s = explode(", ", $userData['roles']);

        foreach($s as $role) {
            $roles[] = $role;
        }
        $user->setRoles($roles);

        return $user;
    }

}
