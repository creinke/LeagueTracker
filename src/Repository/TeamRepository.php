<?php
namespace App\Repository;

use App\Entity\LeagueDE;
use App\Entity\PlayerDE;
use App\Entity\TeamDE;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Class where you can add "persist" and other specialized methods associated with the team table.
 */
class TeamRepository extends AbstractBaseRepository {

	public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
		parent::__construct($em, $logger, TeamDE::class);
    }

    /**
     * Checks to make sure all player-required fields are set
     * This is also where to perform secondary filtering/sanitization of data
     *
     * @param array $playerData
     */
    protected function checkPlayerData(array &$playerData): void {
        $playerData['firstName'] ??= '';
        $playerData['lastName'] ??= '';
        $playerData['middleNameOrInitial'] ??= '';
        $playerData['generation'] ??= '';
    }

    /**
     * Checks to make sure all the player-required fields in the collection are set.
     * This is also where to perform secondary filtering/sanitization of data
     *
     * @param array $playersData
     */
    protected function checkPlayersData(array &$playersData): void {
        for ($i = 0; $i < sizeof($playersData); $i++) {
            $this->checkPlayerData($playersData[$i]);
        }
    }

    /**
     * Checks to make sure all team-required fields are set
     * This is also where to perform secondary filtering/sanitization of data
     *
     * @param array $teamData
     */
    protected function checkTeamData(array &$teamData): void {
        $teamData['defunct'] ??= 'false';
        $teamData['name'] ??= '';
        $teamData['teamNumber'] ??= '';
        $this->checkPlayersData($teamData["players"]['player']);
    }

	/**
	 * @param int $leagueId
	 *
	 * @return float|int|mixed|string
	 * @throws Exception
	 */
	public function findAllTeams(int $leagueId): mixed {
        try {
            // get QB instance
            $qb = $this->createQueryBuilder('team');

            $expr = $qb->expr()->eq('team.league', '?1');
            $qb->setParameter(1, $leagueId);

            $qb->where($expr)
                ->orderBy('team.teamnumber', 'ASC');

            /** @noinspection PhpUnnecessaryLocalVariableInspection */
            $queryResult = $qb->getQuery()->getResult();
            // echo $qb->getQuery()->getSql();
            return $queryResult;
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in %s method for League Id [%s]: %s',
		        "TeamRepository::findAllTeams", $leagueId, $e->getMessage()));
			throw $e;
        }
    }

    /**
     * @param string $name of team
     *
     * @return object|NULL TeamDE
     */
    public function findByName(int $leagueId, string $name) : ?TeamDE {
        return $this->findOneBy(array('league' => $leagueId, 'name' => $name));
    }

	/**
	 * Deletes a team entity
	 *
	 * @param TeamDE $team
	 *
	 * @return TeamDE
	 * @throws Exception
	 */
    public function removeTeam(TeamDE $team): TeamDE {
        try {
            $this->getEntityManager()->remove($team);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in %s for Team [%s]: %s',
		        "TeamRepository::removeTeam", $team->getName(), $e->getMessage()));
	        throw $e;
        }
        return $team;
    }

	/**
	 * Adds or updates team entity
	 *
	 * @param array $teamData new or modified team data
	 * @param LeagueDE $league
	 * @param ?TeamDE $team
	 *
	 * @return ?TeamDE
	 * @throws Exception
	 */
    public function save(array $teamData, LeagueDE $league, ?TeamDE $team = NULL): ?TeamDE {
        $this->checkTeamData($teamData);
        $team = $this->setTeamData($teamData, $league, $team);

        try {
            $this->getEntityManager()->persist($team);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in %s for Team [%s]: %s',
		        "TeamRepository::save", $team->getName(), $e->getMessage()));
            throw $e;
        }
        return $team;
    }

	/**
	 * Adds all team entities
	 *
	 * @param array $teamsData new or modified list of team data
	 *
	 * @return Collection of Entity\TeamDE
	 * @throws Exception
	 */
    public function saveAll(array $teamsData, LeagueDE $league): Collection {
        $teams = new ArrayCollection();

        foreach($teamsData as $teamData) {
            $team = $this->save($teamData, $league);
            $teams->add($team);
        }
        return $teams;
    }

	/**
	 * Adds or updates team entity
	 *
	 * @param TeamDE $team
	 *
	 * @return TeamDE
	 * @throws Exception
	 */
    public function saveTeam(TeamDE $team): TeamDE {
        try {
            $this->getEntityManager()->persist($team);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in %s for Team [%s]: %s',
		        "TeamRepository::saveTeam", $team->getName(), $e->getMessage()));
            throw $e;
        }
        return $team;
    }

	/**
	 * Find the PlayerDE for the player values in $playerData
	 *
	 * @param int $leagueId
	 * @param array $playerData
	 *
	 * @return PlayerDE $player
	 * @throws Exception
	 */
    protected function setPlayerData(int $leagueId, array $playerData): PlayerDE {
        $playerRepository = new PlayerRepository($this->getEntityManager(),$this->getLogger());
        $player = $playerRepository->findPlayerByName($leagueId, $playerData);
        return $player[0];
    }

	/**
	 * Find the PlayerDEs for the players in $playersData
	 *
	 * @param int $leagueId
	 * @param array $playersData array of player data
	 * @param ?Collection $players of Entity\PlayerDE
	 *
	 * @return Collection|null of Entity\PlayerDE
	 * @throws Exception
	 */
    protected function setPlayersData(int $leagueId, array $playersData, ?Collection $players = NULL): ?Collection {
        $players ??= new ArrayCollection();

        foreach($playersData as $playerData) {
            $players[] = $this->setPlayerData($leagueId, $playerData);
        }
        return $players;
    }

	/**
	 * Calls setters to assign $teamData to properties in $team
	 *
	 * @param array $teamData
	 * @param LeagueDE $league
	 * @param ?TeamDE $team
	 *
	 * @return ?TeamDE $team
	 * @throws Exception
	 */
    protected function setTeamData(array $teamData, LeagueDE $league, ?TeamDE $team = NULL): ?TeamDE {
        $team ??= new TeamDE();

        /** @noinspection PhpTernaryExpressionCanBeReplacedWithConditionInspection */
        $team->setDefunct($teamData['defunct'] == "true" ? true : false);
        $team->setName($teamData['name']);
        $team->setTeamnumber($teamData['teamNumber']);
        $team->setPlayers($this->setPlayersData($league->getId(), $teamData['players']['player']));
        $team->setLeague($league);

        return $team;
    }
}