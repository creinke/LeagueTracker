<?php
namespace App\Repository;

use App\Entity\EventDE;
use App\Entity\GameDE;
use App\Model\GameFormatType;
use App\Entity\PlayermatchDE;
use App\Entity\ScoreDE;
use App\Entity\SessionDE;
use App\Entity\TeammatchDE;
use App\Model\EventFormatType;
use App\Model\EventType;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\PersistentCollection;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Class where you can add "persist" and other specialized methods associated with the event table.
 */
class EventRepository extends AbstractBaseRepository {

	public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
		parent::__construct($em, $logger, EventDE::class);
	}

    /**
     * Checks to make sure all event-required fields are set
     * This is also where to perform secondary filtering/sanitization of data
     *
     * @param array $eventData reference $eventData
     */
    protected function checkEventData(array &$eventData): void {
        $eventData['number'] ??= '';
        $eventData['format'] ??= EventFormatType::MATCH_PLAY;
        $eventData['playersperteam'] ??= '2';
        $eventData['nine'] ??= '';
        $eventData['secondNine'] ??= '';
        $eventData['startDateTime'] ??= '';
        $eventData['minutesbetweengames'] ??= '8';
        $eventData['teamsorplayerspergame'] ??= '2';
        $eventData['tee'] ??= '';
        $eventData['type'] ??= EventType::LEAGUE_MATCH;
        $eventData['venu'] ??= '';
        $eventData['withhandicapping'] ??= 'true';
        
        $this->checkGamesData($eventData['type'], $eventData['game']);
    }

	/**
	 * Checks to make sure all game-required fields are set
	 * This is also where to perform secondary filtering/sanitization of data
	 *
	 * @param string $eventType reference $gameData
	 * @param array $gameData
	 */
    protected function checkGameData(string $eventType, array &$gameData): void {
        $gameData['format'] ??= GameFormatType::SINGLES_MATCH_PLAY;
        $gameData['startingTime'] ??= '';

        if ($eventType == EventType::SINGLES_MATCH) {
            if ($gameData['format'] == GameFormatType::SINGLES_STROKE_PLAY) {
                $this->checkPlayersData($gameData['players']);
            } else {
                $this->checkMatchesData($gameData['matches']);
            }
        } else {
            $this->checkTeamMatchData($gameData['match']);
        }
    }

	/**
	 * Checks to make sure all the game-required fields in the collection are set.
	 * This is also where to perform secondary filtering/sanitization of data
	 *
	 * @param string $eventType reference $gamesData
	 * @param array $gameData
	 */
    protected function checkGamesData(string $eventType, array &$gameData): void {
        for ($i = 0; $i < sizeof($gameData); $i++) {
            $this->checkGameData($eventType, $gameData[$i]);
        }
    }

    /**
     * Checks to make sure all match fields are set
     * This is also where to perform secondary filtering/sanitization of data
     *
     * @param array $matchData reference $matchData
     */
    protected function checkMatchData(array &$matchData): void {
        for ($i = 0; $i < sizeof($matchData['match']); $i++) {
            $matchData['match'][$i]['player'] ??= '';
            $matchData['match'][$i]['scores'] ??= '';
        }
    }
    
    /**
     * Checks to make sure all the game-required fields in the collection are set.
     * This is also where to perform secondary filtering/sanitization of data
     *
     * @param array $matchesData reference $playerMatchesData
     */
    protected function checkMatchesData(array &$matchesData): void {
        for ($i = 0; $i < sizeof($matchesData); $i++) {
            $this->checkMatchData($matchesData[$i]);
        }
    }
    
    /**
     * Checks to make sure all player match fields are set
     * This is also where to perform secondary filtering/sanitization of data
     *
     * @param array $playerMatchData reference $playerMatchData
     */
    protected function checkPlayerMatchData(array &$playerMatchData): void {
        $opponents = $playerMatchData['opponents'];
        
        for ($i = 0; $i < sizeof($opponents); $i++) {
            $playerMatchData['opponents'][$i]['player'] ??= '';
            $playerMatchData['opponents'][$i]['scores'] ??= '';
        }
    }

    /**
     * Checks to make sure all the games required fields in the collection are set.
     * This is also where to perform secondary filtering/sanitization of data
     *
     * @param array $playerMatchesData reference $playerMatchesData
     */
    protected function checkPlayerMatchesData(array &$playerMatchesData): void {
        for ($i = 0; $i < sizeof($playerMatchesData); $i++) {
            $this->checkPlayerMatchData($playerMatchesData[$i]);
        }
    }

    /**
     * Checks to make sure all players in the game are set
     * This is also where to perform secondary filtering/sanitization of data
     *
     * @param array $playersData reference $playersData
     */
    protected function checkPlayersData(array &$playersData): void {
        for ($i = 0; $i < sizeof($playersData); $i++) {
            $playersData[$i]['player'] ??= '';
            $playersData[$i]['scores'] ??= '';
        }
    }
    
    /**
     * Checks to make sure all team match required fields are set
     * This is also where to perform secondary filtering/sanitization of data
     *
     * @param array $teamMatchData reference $teamMatchData
     */
    protected function checkTeamMatchData(array &$teamMatchData): void {
        $teamMatchData['opponents'][0]['team'] ??= '';
        $teamMatchData['opponents'][1]['team'] ??= '';

        $this->checkPlayerMatchesData($teamMatchData['match']);
    }

	/**
	 * Find first event by event type and format type with at least one game
	 *
	 * @param int $eventType The event type (1-5 corresponding to EventType constants)
	 * @param int $formatType The format type (1-6 corresponding to EventFormatType constants)
	 *
	 * @return EventDE|null First EventDE entity matching the criteria or null if not found
	 * @throws NonUniqueResultException
	 */
public function findFirstByEventTypeAndFormat(int $eventType, int $formatType): ?EventDE {
	try {
		$qb = $this->createQueryBuilder('e')
			->leftJoin('e.games', 'g')
			->where('e.eventtype = :eventType')
			->andWhere('e.format = :format')
			->setParameter('eventType', $eventType)
			->setParameter('format', $formatType)
			->groupBy('e.id')
			->having('COUNT(g.id) > 0')
			->orderBy('e.id', 'ASC')
			->setMaxResults(1);

		return $qb->getQuery()->getOneOrNullResult();
	} catch (Exception $e) {
		$this->logError(sprintf('Error in the %s method for eventType [%d] and format [%d]: %s',
			'EventRepository::findFirstByEventTypeAndFormat', $eventType, $formatType, $e->getMessage()));
		throw $e;
	}
}

/**
 * Checks to make sure all event-required fields are set
 * This is also where to perform secondary filtering/sanitization of data
// ... existing code ...	/**
	 * @throws NonUniqueResultException
	 */
	public function findFirstEventWithMoreThanOneGame(): ?EventDE	{
		$qb = $this->createQueryBuilder('e')
			->leftJoin('e.games', 'g')
			->where('e.eventtype < 4')
			->groupBy('e.id')
			->having('COUNT(g.id) > 1')
			->orderBy('e.id', 'ASC')
			->setMaxResults(1);

		return $qb->getQuery()->getOneOrNullResult();
	}

	/**
	 * @throws NonUniqueResultException
	 */
	public function findFirstEventWithNoGames(): ?EventDE	{
		$qb = $this->createQueryBuilder('e')
			->leftJoin('e.games', 'g')
			->where('e.eventtype < 4')
			->groupBy('e.id')
			->having('COUNT(g.id) = 0')
			->orderBy('e.id', 'ASC')
			->setMaxResults(1);

		return $qb->getQuery()->getOneOrNullResult();
	}

	/**
	 * Find first team event with at least one teamgame
	 *
	 * @return EventDE|null First EventDE entity matching the criteria or null if not found
	 * @throws NonUniqueResultException
	 */
	public function findFirstTeamEventWithTeamgame(): ?EventDE {
		try {
			$qb = $this->createQueryBuilder('e')
				->leftJoin('e.teamgames', 'tg')
				->where('e.eventtype = 4')
				->groupBy('e.id')
				->having('COUNT(tg.id) > 0')
				->orderBy('e.id', 'ASC')
				->setMaxResults(1);

			return $qb->getQuery()->getOneOrNullResult();
		} catch (Exception $e) {
			$this->logError(sprintf('Error in the %s method : %s',
				'EventRepository::findFirstTeamEventWithTeamgame', $e->getMessage()));
			throw $e;
		}
	}

	/**
	 * Find first team event by format type with at least one teamgame
	 *
	 * @param int $formatType The format type (2-5 corresponding to team EventFormatType constants)
	 *
	 * @return EventDE|null First EventDE entity matching the criteria or null if not found
	 * @throws NonUniqueResultException
	 */
	public function findFirstTeamEventByFormat(int $formatType): ?EventDE {
		try {
			$qb = $this->createQueryBuilder('e')
			           ->leftJoin('e.teamgames', 'tg')
			           ->where('e.eventtype = 4')
			           ->andWhere('e.format = :format')
			           ->setParameter('format', $formatType)
			           ->groupBy('e.id')
			           ->having('COUNT(tg.id) > 0')
			           ->orderBy('e.id', 'ASC')
			           ->setMaxResults(1);

			return $qb->getQuery()->getOneOrNullResult();
		} catch (Exception $e) {
			$this->logError(sprintf('Error in the %s method for format [%d]: %s',
				'EventRepository::findFirstTeamEventByFormat', $formatType, $e->getMessage()));
			throw $e;
		}
	}

	/**
	 * @param DateTime $eventStartDateAndTime
	 * @param array $gameData
	 *
	 * @return DateTime
	 * @throws Exception
	 */
    protected function gameDateTime(DateTime $eventStartDateAndTime, array $gameData): DateTime {
        $gameTime = DateTime::createFromFormat("H:i:s", $gameData['startingTime']);
        $gameDateAndTime = new DateTime("@" . (string) $eventStartDateAndTime->getTimestamp());
        $d = getDate($gameTime->getTimestamp());
        $gameDateAndTime->setTime($d['hours'], $d['minutes']);
        return $gameDateAndTime;
    }

	/**
	 * @param EventDE $event
	 * @param array $gameData
	 * @param GameDE $game
	 * @param array $playerMatchData
	 *
	 * @return PlayermatchDE
	 * @throws Exception
	 */
    protected function playerMatch(EventDE $event, array $gameData, GameDE $game, array $playerMatchData) : PlayermatchDE {
        $playerMatch = new PlayermatchDE();
        $playerMatch->setGame($game);
        $playerMatch->setPlayerscores(new PersistentCollection($this->getEntityManager(), new ClassMetadata('App\Entity\ScoreDE'), new ArrayCollection()));
        
        $playerRepository = new PlayerRepository($this->getEntityManager(),$this->getLogger());
        $scoreRepository = new ScoreRepository($this->getEntityManager(),$this->getLogger());;

        $league = $event->getSession()->getSeason()->getLeague();
        $teeName = $event->getTee()->getName();
        $gameStartDateAndTime = $this->gameDateTime($event->getStartdateandtime(), $gameData);

        $opponents = $playerMatchData['opponents'];
        $playerOne = $playerRepository->findPlayerByNameString($league->getId(), $opponents[0]['player'])[0];
        $playerMatch->setPlayerone($playerOne);
        $playerTwo = $playerRepository->findPlayerByNameString($league->getId(), $opponents[1]['player'])[0];
        $playerMatch->setPlayertwo($playerTwo);
        
        for ($opponent = 0; $opponent < sizeof($opponents); $opponent++) {
            $playerScores = $opponents[$opponent]['scores'];
            
            for ($score = 0; $score < sizeof($playerScores); $score++) {
                $playerScore = new ScoreDE($playerScores[$score]['score']);
                
                if ($opponent === 0) {
                    $playerScore->setPlayer($playerOne);
                } else {
                    $playerScore->setPlayer($playerTwo);
                }
                
                if ($score === 0) {
                    $nine = $event->getNine();
                } else {
                    $nine = $event->getSecondnine();
                }
                
                foreach($nine->getTees() as $tee) {
                    if ($tee->getName() == $teeName) {
                        $playerScore->setTee($tee);
                        break;
                    }
                }
                $playerScore->setTimestamp($gameStartDateAndTime);
                $scores = $scoreRepository->findPlayerScores($playerScore->getPlayer(), $event->getStartdateandtime());
                
                if (sizeof($scores) > 20) {
                    $scores = array_slice($scores, 0, 20);
                }
                $scoresRecorded = sizeof($scores);
                $playerHandicapCalculationResult = $scoreRepository->calculatePlayerHandicapIndex($playerScore->getPlayer(), $event->getStartdateandtime(), $scores);
                $playerScore->setCurrenthandicapindex($playerHandicapCalculationResult['currentHandicapIndex']);
                $playerScore->setHandicapdifferential($playerScore->calculateHandicapDifferential($scoresRecorded));
                
                $playerMatch->getPlayerscores()->add($playerScore);
            }
        }
        return $playerMatch;
    }

	/**
	 * @param EventDE $event
	 * @param array $gameData
	 * @param GameDE $game
	 * @param array $playerMatchesData
	 *
	 * @return PersistentCollection
	 * @throws Exception
	 */
    protected function playerMatches(EventDE $event, array $gameData, GameDE $game, array $playerMatchesData): PersistentCollection {
        $playerMatches = new PersistentCollection($this->getEntityManager(), new ClassMetadata('App\Entity\PlayermatchDE'), new ArrayCollection());
        
        for ($i = 0; $i < sizeof($playerMatchesData); $i++) {
            $playerMatch = $this->playerMatch($event, $gameData, $game, $playerMatchesData[$i]);
            $playerMatches[] = $playerMatch;
        }
        for ($i = 0; $i < sizeof($playerMatches); $i++) {
            $playerMatch = $playerMatches[$i];
            $playerId = $playerMatch->getPlayerone()->getId();
            
            if ($playerId == $playerMatch->getPlayertwo()->getId()) {
                foreach($playerMatch->getPlayertwoscores() as $playerTwoScore) {
                    $playerTwoScore->setDuplicatescore(true);
                }
            }
            for ($j = $i + 1; $j < sizeof($playerMatchesData); $j++) {
                $playerMatch = $playerMatches[$j];
                
                if ($playerId == $playerMatch->getPlayerone()->getId()) {
                    foreach($playerMatch->getPlayeronescores() as $playerOneScore) {
                        $playerOneScore->setDuplicatescore(true);
                    }
                }
                if ($playerId == $playerMatch->getPlayertwo()->getId()) {
                    foreach($playerMatch->getPlayertwoscores() as $playerTwoScore) {
                        $playerTwoScore->setDuplicatescore(true);
                    }
                }
            }
        }
        return $playerMatches;
    }

	/**
	 * @param EventDE $event
	 * @param array $gameData
	 * @param GameDE $game
	 * @param array $playersData
	 *
	 * @return array $playerScores
	 * @throws Exception
	 */
    protected function playerScores(EventDE $event, array $gameData, GameDE $game, array $playersData) : array {
        $playerScores = [];
        $playerRepository = new PlayerRepository($this->getEntityManager(), $this->getLogger());
        $scoreRepository = new ScoreRepository($this->getEntityManager(), $this->getLogger());
        
        $league = $event->getSession()->getSeason()->getLeague();
        $teeName = $event->getTee()->getName();
        $gameStartDateAndTime = $this->gameDateTime($event->getStartdateandtime(), $gameData);
        
        for ($playerIndex = 0; $playerIndex < sizeof($playersData); $playerIndex++) {
            $player = $playerRepository->findPlayerByNameString($league->getId(), $playersData[$playerIndex]['player'])[0];
            
            $scores = [];
            $playerDataScores = $playersData[$playerIndex]['scores'];
            
            for ($score = 0; $score < sizeof($playerDataScores); $score++) {
                $playerScore = new ScoreDE($playerDataScores[$score]['score']);
                $playerScore->setPlayer($player);
                
                if ($score == 0) {
                    $nine = $event->getNine();
                } else {
                    $nine = $event->getSecondnine();
                }
                foreach($nine->getTees() as $tee) {
                    if ($tee->getName() == $teeName) {
                        $playerScore->setTee($tee);
                        break;
                    }
                }
                $playerScore->setTimestamp($gameStartDateAndTime);
                $allPlayerScores = $scoreRepository->findPlayerScores($playerScore->getPlayer(), $event->getStartdateandtime());
                
                if (sizeof($allPlayerScores) > 20) {
                    $allPlayerScores = array_slice($allPlayerScores, 0, 20);
                }
                $scoresRecorded = sizeof($allPlayerScores);
                $playerHandicapCalculationResult = $scoreRepository->calculatePlayerHandicapIndex($playerScore->getPlayer(), $event->getStartdateandtime(), $allPlayerScores);
                $playerScore->setCurrenthandicapindex($playerHandicapCalculationResult['currentHandicapIndex']);
                $playerScore->setHandicapdifferential($playerScore->calculateHandicapDifferential($scoresRecorded));
                
                $scores[] = $playerScore;
            }
            $playerScores[] = [$player, $scores];
        }
        return $playerScores;
    }

	/**
	 * Deletes an event entity
	 *
	 * @param EventDE $event Entity\EventDE $event
	 *
	 * @return EventDE Entity\EventDE
	 * @throws Exception
	 */
    public function removeEvent(EventDE $event): EventDE {
        try {
            $this->getEntityManager()->remove($event);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method for event Id [%s]: %s',
		        'EventRepository::removeEvent', $event->getId(), $e->getMessage()));
			throw $e;
        }
        return $event;
    }

	/**
	 * Adds or updates event entity
	 *
	 * @param array $eventData new or modified event data
	 * @param SessionDE $session Entity\SessionDE $session
	 *
	 * @return EventDE Entity\EventDE
	 * @throws Exception
	 */
    public function save(array $eventData, SessionDE $session): EventDE {
        $this->checkEventData($eventData);
        $event = $this->setEventData($eventData, $session);

        try {
            $this->getEntityManager()->persist($event);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method: %s',
		        'EventRepository::save', $e->getMessage()));
			throw $e;
        }
        return $event;
    }

	/**
	 * Adds all event entities
	 *
	 * @param array $eventsData new or modified list of event data
	 * @param SessionDE $session
	 *
	 * @return PersistentCollection PersistentCollection of Entity\EventDE
	 * @throws Exception
	 */
    public function saveAll(array $eventsData, SessionDE $session): PersistentCollection {
        $events = new PersistentCollection($this->getEntityManager(), new ClassMetadata('App\Entity\EventDE'), new ArrayCollection());

        foreach($eventsData as $eventData) {
            $events[] = $this->save($eventData, $session);
        }
        return $events;
    }

	/**
	 * Adds or updates event entity
	 *
	 * @param EventDE $event EventDE $event
	 *
	 * @return EventDE EventDE
	 * @throws Exception
	 */
    public function saveEvent(EventDE $event): EventDE {
        try {
            $this->getEntityManager()->persist($event);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method: %s',
		        'EventRepository::saveEvent', $e->getMessage()));
			throw $e;
        }
        return $event;
    }

	/**
	 * Calls setters to assign $eventData to properties in $event
	 *
	 * @param array $eventData
	 * @param SessionDE $session Entity\SessionDE $session
	 *
	 * @return EventDE Entity\EventDE $event
	 * @throws Exception
	 */
    protected function setEventData(array $eventData, SessionDE $session) : EventDE {
        $event = new EventDE($this->getEntityManager());
        $event->setEventnumber($eventData['number']);
        $event->setEventtype(EventType::toOrdinal($eventData['type']));
        $event->setFormat(EventFormatType::toOrdinal($eventData['format']));

        $courseRepository = new CourseRepository($this->getEntityManager(), $this->getLogger());;
        $course = $courseRepository->findCourseByName($eventData['venu']);

        if ($course) {
            $event->setCourse($course);
            $nine = $course->findNineByName($eventData['nine']);

            if ($nine) {
                $tee = $nine->findTeeByName($eventData['tee']);

                if ($tee) {
                    $event->setNine($nine);
                    $event->setTee($tee);
                }
            }
            $secondNine = $course->findNineByName($eventData['secondNine']);
            
            if ($secondNine) {
                $event->setSecondNine($secondNine);
            }
        }
        $format = "Y-m-d\TH:i:s";
        $startDateAndTime = DateTime::createFromFormat($format, $eventData['startDateTime']);
        $event->setStartdateandtime($startDateAndTime);
        $event->setPlayersperteam($eventData['playersperteam']);
        $event->setTeamsorplayerspergame($eventData['teamsorplayerspergame']);
        $event->setWithhandicapping($eventData['withhandicapping'] == 'true' ? true : false);
        $event->setMinutesbetweengames($eventData['minutesbetweengames']);
        $event->setSession($session);

        $event->setGames($this->setGamesData($event, $eventData['game']));

        return $event;
    }

	/**
	 * Calls setters to assign $gameData to properties in $game
	 *
	 * @param EventDE $event Entity\EventDE $event
	 * @param array $gameData
	 * @param GameDE|null $game
	 *
	 * @return GameDE Entity\GameDE $game
	 * @throws Exception
	 */
    protected function setGameData(EventDE $event, array $gameData, GameDE $game = NULL): GameDE {
        if (!$game) {
            $game = new GameDE($this->getEntityManager());
        }
        $game->setRecorded(true);
        $game->setFormat(GameFormatType::toOrdinal($gameData['format']));
        $game->setEvent($event);

        $format = "H:i:s";
        $startingTime = DateTime::createFromFormat($format, $gameData['startingTime']);
        $game->setStartingtime($startingTime);

        if ($event->isSinglesMatch($event->getEventtype()))
            if ($event->isStrokePlay($event->getFormat())) {
                $playersData = $gameData['players'];
                $playerScores = $this->playerScores($event, $gameData, $game, $playersData);
                
                foreach($playerScores as $playerScore) {
                    $game->getPlayers()->add($playerScore[0]);
                    
                    foreach($playerScore[1] as $score) {
                        $game->getPlayerscores()->add($score);
                    }
                }
            } else {
                $matches = $gameData['matches'];
                
                foreach($matches as $match) {
                    $playerData = $match['match'];
                    $playerScores = $this->playerScores($event, $gameData, $game, $playerData);
                    
                    $playerMatch = new PlayermatchDE();
                    $playerMatch->setGame($game);
                    $playerMatch->setPlayerscores(new PersistentCollection($this->getEntityManager(), new ClassMetadata('App\Entity\ScoreDE'), new ArrayCollection()));
                    
                    $playerMatch->setPlayerone($playerScores[0][0]);
                    $playerMatch->setPlayertwo($playerScores[1][0]);
                    
                    foreach($playerScores as $playerScore) {
                        foreach($playerScore[1] as $score) {
                            $playerMatch->getPlayerscores()->add($score);
                        }
                    }
                    
                    $game->getPlayermatches()->add($playerMatch);
                }
            } else {
            $teamMatchData = $gameData['match'];
            $game->setTeammatches($this->teamMatches($event, $game, $teamMatchData));
    
            $playerMatchesData = $teamMatchData['match'];
            $game->setPlayermatches($this->playerMatches($event, $gameData, $game, $playerMatchesData));
        }
        return $game;
    }

	/**
	 * @param EventDE $event Entity\EventDE $event
	 * @param array $gamesData
	 *
	 * @return PersistentCollection
	 * @throws Exception
	 */
    protected function setGamesData(EventDE $event, array $gamesData): PersistentCollection {
        $games = new PersistentCollection($this->getEntityManager(), new ClassMetadata('App\Entity\GameDE'), new ArrayCollection());

        foreach($gamesData as $gameData) {
            $games[] = $this->setGameData($event, $gameData);
        }
        return $games;
    }

	/**
	 * Calls setters to assign $teamMatchData to properties in $teammatch
	 *
	 * @param array $teamMatchData
	 * @param TeammatchDE|null $teammatch Entity\TeammatchDE $teammatch
	 *
	 * @return TeammatchDE Entity\TeammatchDE $teammatch
	 */
    protected function setTeamMatchData(array $teamMatchData, TeammatchDE $teammatch = NULL): TeammatchDE {
        $teammatch ??= new TeammatchDE();

        // $teammatch->setTeamone($teamone);
        // $teammatch->setTeamtwo($teamtwo);

        return $teammatch;
    }

	/**
	 * @param EventDE $event
	 * @param GameDE $game
	 * @param array $teamMatchData
	 *
	 * @return PersistentCollection of TeammatchDEs
	 */
    protected function teamMatches(EventDE $event, GameDE $game, array $teamMatchData): PersistentCollection {
        $league = $event->getSession()->getSeason()->getLeague();
        $teamRepository = new TeamRepository($this->getEntityManager(), $this->getLogger());

        $teamOneName = $teamMatchData['opponents'][0]['team'];
        $teamOne = $teamRepository->findByName($league->getId(), $teamOneName);

        $teamTwoName = $teamMatchData['opponents'][1]['team'];
        $teamTwo = $teamRepository->findByName($league->getId(), $teamTwoName);

        $teamMatch = new TeammatchDE();
        $teamMatch->setGame($game);
        $teamMatch->setTeamone($teamOne);
        $teamMatch->setTeamtwo($teamTwo);

        $teamMatches = new PersistentCollection($this->getEntityManager(), new ClassMetadata('App\Entity\TeammatchDE'), new ArrayCollection());
        $teamMatches[] = $teamMatch;

        return $teamMatches;
    }
}