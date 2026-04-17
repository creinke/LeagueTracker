<?php
namespace App\Repository;

use App\Entity\EventDE;
use App\Entity\GameDE;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Class where you can add "persist" and other specialized methods associated with the game table.
 */
class GameRepository extends AbstractBaseRepository {

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
        parent::__construct($em, $logger, GameDE::class);
    }

	/**
	 * Deletes a game entity
	 *
	 * @param GameDE $game
	 *
	 * @return GameDE
	 * @throws Exception
	 */
    public function removeGame(GameDE $game): GameDE {
        try {
            $this->getEntityManager()->remove($game);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method for game Id [%s]: %s',
		        'GameRepository::removeGame', $game->getId(), $e->getMessage()));
	        throw $e;
		}
        return $game;
    }


	/**
	 * Reorders the player matches for the event based on current player handicaps.
	 *
	 * @param EventDE $event
	 * @param GameDE $game
	 *
	 * @throws Exception
     * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
     */
    public function reorderPlayerMatchesIfNecessary(EventDE $event, GameDE &$game): void {
    	$scoreRepository = new ScoreRepository($this->getEntityManager(), $this->getLogger());
    	
    	$saveGame = false;
    	$playerMatches = $game->getPlayermatches();
    	
    	for ($i = 0; $i < $playerMatches->count() - 1; $i++) {
    		$playerMatch = $playerMatches[$i];
    		
    		if (!empty($playerMatch->getPlayerscores()) && $playerMatch->getPlayerscores()->count() > 0) {
    			return;
    		}
    	}
    	for ($i = 0; $i < $playerMatches->count() - 1; $i++) {
    		$playerMatchOne = $playerMatches[$i];
    		$playerOne = $playerMatchOne->getPlayerone();
    		$playerOneHandicapIndex = $scoreRepository->calculatePlayerHandicapIndex($playerOne, $event->getStartdateandtime())['currentHandicapIndex'];
    		
    		for ($j = $i + 1; $j < $playerMatches->count(); $j++) {
    			$playerMatchTwo = $playerMatches[$j];
    			$playerTwo = $playerMatchTwo->getPlayerone();
    			$playerTwoHandicapIndex = $scoreRepository->calculatePlayerHandicapIndex($playerTwo, $event->getStartdateandtime())['currentHandicapIndex'];
    			
    			if ($playerTwoHandicapIndex < $playerOneHandicapIndex) {
    				$saveGame = true;
    				
    				$playerMatchOne->setPlayerone($playerTwo);
    				$playerOneHandicapIndex = $playerTwoHandicapIndex;
    				$playerMatchTwo->setPlayerone($playerOne);
    			}
    		}
    	}
    	for ($i = 0; $i < $playerMatches->count() - 1; $i++) {
    		$playerMatchOne = $playerMatches[$i];
    		$playerOne = $playerMatchOne->getPlayertwo();
    		$playerOneHandicapIndex = $scoreRepository->calculatePlayerHandicapIndex($playerOne, $event->getStartdateandtime())['currentHandicapIndex'];
    		
    		for ($j = $i + 1; $j < $playerMatches->count(); $j++) {
    			$playerMatchTwo = $playerMatches[$j];
    			$playerTwo = $playerMatchTwo->getPlayertwo();
    			$playerTwoHandicapIndex = $scoreRepository->calculatePlayerHandicapIndex($playerTwo, $event->getStartdateandtime())['currentHandicapIndex'];
    			
    			if ($playerTwoHandicapIndex < $playerOneHandicapIndex) {
    				$saveGame = true;
    				
    				$playerMatchOne->setPlayertwo($playerTwo);
    				$playerOneHandicapIndex = $playerTwoHandicapIndex;
    				$playerMatchTwo->setPlayertwo($playerOne);
    			}
    		}
    	}
    	if ($saveGame) {
    		$this->saveGame($game);
    	}
    }

	/**
	 * When one player needs to play a different play because of handicaps, this method is called.  Generally this
	 * happens when a player gets a sub or a player's handicap improves or gets worse, making it necessary to change the
	 * position of the player in the match/game.
	 *
	 * I'm not sure if I use this method anymore.  But I'm keeping it around anyway to be sure.  It looks like I use
	 * the reorderPlayerMatchesIfNecessary method instead for this purpose.
	 *
	 * @param EventDE $event
	 * @param GameDE $game
	 *
	 * @return void
	 * @throws Exception
     * @noinspection PhpUnused
     * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
     */
	public function reversePlayerMatchesIfNecessary(EventDE $event, GameDE &$game): void {
        $scoreRepository = new ScoreRepository($this->getEntityManager(), $this->getLogger());
        $playermatchRepository = new PlayermatchRepository($this->getEntityManager(), $this->getLogger());

        $playerMatches = $game->getPlayermatches();
        $playerMatchOne = $playerMatches[0];
        $playerMatchTwo = $playerMatches[1];

        /** @noinspection DuplicatedCode */
        $playerOne = $playerMatchOne->getPlayerone();
        $playerOneScore = $playerMatchOne->getPlayeronescore();
        $playerOneHandicapIndex = $scoreRepository->calculatePlayerHandicapIndex($playerOne, $event->getStartdateandtime())['currentHandicapIndex'];

        $playerTwo = $playerMatchOne->getPlayertwo();
        $playerTwoScore = $playerMatchOne->getPlayertwoscore();
        $playerTwoHandicapIndex = $scoreRepository->calculatePlayerHandicapIndex($playerTwo, $event->getStartdateandtime())['currentHandicapIndex'];

        /** @noinspection DuplicatedCode */
        $playerThree = $playerMatchTwo->getPlayerone();
        $playerThreeScore = $playerMatchTwo->getPlayeronescore();
        $playerThreeHandicapIndex = $scoreRepository->calculatePlayerHandicapIndex($playerThree, $event->getStartdateandtime())['currentHandicapIndex'];

        $playerFour = $playerMatchTwo->getPlayertwo();
        $playerFourScore = $playerMatchTwo->getPlayertwoscore();
        $playerFourHandicapIndex = $scoreRepository->calculatePlayerHandicapIndex($playerFour, $event->getStartdateandtime())['currentHandicapIndex'];

        $updatePlayerMatches = false;

        if ($playerThreeHandicapIndex < $playerOneHandicapIndex) {
            $playerMatchOne->setPlayerone($playerThree);
            $playerMatchOne->setPlayeronescore($playerThreeScore);

            $playerMatchTwo->setPlayerone($playerOne);
            $playerMatchTwo->setPlayeronescore($playerOneScore);

            $updatePlayerMatches = true;
        }
        if ($playerFourHandicapIndex < $playerTwoHandicapIndex) {
            $playerMatchOne->setPlayertwo($playerFour);
            $playerMatchOne->setPlayertwoscore($playerFourScore);

            $playerMatchTwo->setPlayertwo($playerTwo);
            $playerMatchTwo->setPlayertwoscore($playerTwoScore);

            $updatePlayerMatches = true;
        }
        if ($updatePlayerMatches) {
            $playermatchRepository->savePlayermatch($playerMatchOne);
            $playermatchRepository->savePlayermatch($playerMatchTwo);
        }
    }

	/**
	 * Adds or updates game entity
	 *
	 * @param GameDE $game
	 *
	 * @return GameDE
	 * @throws Exception
	 */
    public function saveGame(GameDE $game): GameDE {
        try {
            $this->getEntityManager()->persist($game);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method: %s',
		        'CourseRepository::removeCourse', $e->getMessage()));
	        throw $e;
        }
        return $game;
    }
}