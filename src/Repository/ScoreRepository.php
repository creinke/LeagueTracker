<?php
namespace App\Repository;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\PlayerDE;
use App\Entity\ScoreDE;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;


/**
 * Class where you can add "persist" and other specialized methods associated with the score table.
 */
class ScoreRepository extends AbstractBaseRepository {

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
        parent::__construct($em, $logger, ScoreDE::class);
    }

	/**
	 * @param PlayerDE $player
	 * @param DateTime $timestamp
	 * @param array|null $s
	 *
	 * @return array $result
	 * @throws Exception
	 */
    public function calculatePlayerHandicapIndex(PlayerDE $player, DateTime $timestamp, ?array $s = NULL) : array {
        if ($s == NULL) {
            $s = $this->findPlayerScores($player, $timestamp);
        }
        if ($s == NULL) {
            $scores = array();
            $scoresRecorded = 0;
        } else {
            $scores = array_slice($s, 0, 20);
            $scoresRecorded = sizeof($scores);
        }
        $seedHandicapIndex = $player->getSeedhandicapindex();
        $result = array('scores' => $scores, 'scoresRecorded' => $scoresRecorded, 'seedHandicapIndex' => $seedHandicapIndex);

        if ($scoresRecorded < 3) {
            $result['currentHandicapIndex'] = $seedHandicapIndex;
            $result['handicapRelevantScoreCount'] = 0;
            $result['handicapRelevantScores'] = array();
        } else {
            $handicapDifferential = 0;
            $handicapRelevantScoreCount = $this->handicapRelevantScoreCount($scoresRecorded);
            $handicapRelevantScores = $this->lastNLowestScores($scores, $handicapRelevantScoreCount);

            foreach($handicapRelevantScores as $score) {
                $handicapDifferential += $score->getHandicapDifferential();
            }
            $currentHandicapIndex = $handicapDifferential / $handicapRelevantScoreCount;
            
            if ($scoresRecorded == 3) {
                $currentHandicapIndex -= 2.0;
            } else if ($scoresRecorded == 4 || $scoresRecorded == 6) {
                $currentHandicapIndex -= 1.0;
            }
            $i = (int) ($currentHandicapIndex * 10);
            $currentHandicapIndex = (float) $i / 10;

            $result['currentHandicapIndex'] = $currentHandicapIndex;
            $result['handicapRelevantScoreCount'] = $handicapRelevantScoreCount;
            $result['handicapRelevantScores'] = $handicapRelevantScores;
        }
        return $result;
    }

    /**
     * Checks to make sure all score-required fields are set
     * This is also where to perform secondary filtering/sanitization of data
     *
     * @param array $scoreData
     */
    protected function checkScoreData(array &$scoreData): void {
        $scoreData['srokes'] ??= '';
    }

    /**
     * @param int $id of score
     *
     * @return ScoreDE
     */
    public function findById(int $id): ScoreDE {
        return $this->findOneBy(array('id' => $id));
    }

	/**
	 * @param PlayerDE $player
	 * @param DateTime $timestamp
	 *
	 * @return mixed ScoreDEs for player specified
	 * @throws Exception
     * @noinspection PhpUnused
     */
    public function findAllPlayerScores(PlayerDE $player, DateTime $timestamp): mixed {
        try {
            // Crete QB instance and statement
            $qb = $this->createQueryBuilder('score');
            $qb->where($qb->expr()->eq('score.player', '?1'))
                ->andWhere($qb->expr()->lt('score.timestamp', '?2'))
                ->orderBy('score.timestamp', 'DESC')
                ->setParameter(1, $player->getId())
                ->setParameter(2, $timestamp);
            
            // echo $qb->getQuery()->getSql();
            /** @noinspection PhpUnnecessaryLocalVariableInspection */
            $queryResult = $qb->getQuery()->getResult();
            return $queryResult;
        } catch (Exception $e) {
            /** @noinspection PhpExceptionImmediatelyRethrownInspection */
            throw $e;
        }
    }

	/**
	 * @param PlayerDE $player
	 * @param DateTime $timestamp
	 *
	 * @return mixed ScoreDEs for player specified
	 * @throws Exception
	 */
    public function findPlayerScores(PlayerDE $player, DateTime $timestamp): mixed {
        try {
            // Crete QB instance and statement
            $qb = $this->createQueryBuilder('score');
            $qb->where($qb->expr()->eq('score.player', '?1'))
                ->andWhere($qb->expr()->eq('score.duplicatescore', '0'))
                ->andWhere($qb->expr()->eq('score.partialscore', '0'))
                ->andWhere($qb->expr()->lt('score.timestamp', '?2'))
                ->orderBy('score.timestamp', 'DESC')
                ->setParameter(1, $player->getId())
                ->setParameter(2, $timestamp);

            // echo $qb->getQuery()->getSql();
            /** @noinspection PhpUnnecessaryLocalVariableInspection */
            $queryResult = $qb->getQuery()->getResult();
            return $queryResult;
        } catch (Exception $e) {
            /** @noinspection PhpExceptionImmediatelyRethrownInspection */
            throw $e;
        }
    }

    /**
     * @param int $scoresRecorded
     * @return number of handicap relavent scores to be used
     */
    private function handicapRelevantScoreCount(int $scoresRecorded) : int {
        if ($scoresRecorded >= 3 && $scoresRecorded <= 5) {
            $scoresUsed = 1;
        } else if ($scoresRecorded >= 6 && $scoresRecorded <= 8) {
            $scoresUsed = 2;
        } else if ($scoresRecorded >= 9 && $scoresRecorded <= 11) {
            $scoresUsed = 3;
        } else if ($scoresRecorded >= 12 && $scoresRecorded <= 14) {
            $scoresUsed = 4;
        } else if ($scoresRecorded >= 15 && $scoresRecorded <= 16) {
            $scoresUsed = 5;
        } else if ($scoresRecorded >= 17 && $scoresRecorded <= 18) {
            $scoresUsed = 6;
        } else if ($scoresRecorded == 19) {
            $scoresUsed = 7;
        } else if ($scoresRecorded == 20) {
            $scoresUsed = 8;
        } else {
            $scoresUsed = 0;
        }
        return $scoresUsed;
    }

    /**
     * @param array $scores
     * @param int $n
     * @return string[] of last n handicap relevant lowest scores
     */
    private function lastNLowestScores(array $scores, int $n): array {
        $scoresRecorded = sizeOf($scores);
        $n = min($n, $scoresRecorded);
        $nLowestScores = array();

        for ($x = 0; $x < $scoresRecorded; $x++) {
            for ($y = $x + 1; $y < $scoresRecorded; $y++) {
                if ($scores[$y]->getHandicapDifferential() < $scores[$x]->getHandicapDifferential()) {
                    $s = $scores[$x];
                    $scores[$x] = $scores[$y];
                    $scores[$y] = $s;
                }
            }
        }
        for ($x = 0; $x < $n; $x++) {
            $nLowestScores[] = $scores[$x];
        }
        return $nLowestScores;
    }

    /**
     * @param string $n
     *
     * @return string of packed hexidecimal integers
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function pack(string $n): string {
        $a = explode(', ', $n);
        $l = sizeof($a);

        $s = "";
        for ($i = 0; $i < $l; $i++) {
            $x = (intval($a[$i]));
            $s .= sprintf('%1x', $x);
        }
        return $s;
    }

	/**
	 * Deletes a score entity
	 *
	 * @param ScoreDE $score
	 *
	 * @return ScoreDE
	 * @throws Exception
	 */
    public function removeScore(ScoreDE $score): ScoreDE {
        try {
            $this->getEntityManager()->remove($score);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method for score Id [%s]: %s',
		        'ScoreRepository::removeScore', $score->getId(), $e->getMessage()));
            throw $e;
        }
        return $score;
    }

	/**
	 * Adds or updates score entity
	 *
	 * @param array $scoreData new or modified score data
	 * @param ScoreDE|null $score
	 *
	 * @return ScoreDE
	 * @throws Exception
	 */
    public function save(array $scoreData, ?ScoreDE $score = NULL): ScoreDE {
        $this->checkScoreData($scoreData);
        $score = $this->setScoreData($scoreData, $score);

        try {
            $this->getEntityManager()->persist($score);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method: %s',
		        'ScoreRepository::save', $e->getMessage()));
            throw $e;
        }
        return $score;
    }

	/**
	 * Adds or updates score entity
	 *
	 * @param ScoreDE $score
	 *
	 * @return ScoreDE
	 * @throws Exception
	 */
    public function saveScore(ScoreDE $score): ScoreDE {
        try {
            $this->getEntityManager()->persist($score);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method: %s',
		        'ScoreRepository::saveScore', $e->getMessage()));
            throw $e;
        }
        return $score;
    }

	/**
	 * Calls setters to assign $scoreData to properties in $course
	 *
	 * @param array $scoreData
	 * @param ScoreDE|null $score
	 *
	 * @return ScoreDE
	 * @returnScoreDE $score
	 */
    protected function setScoreData(array $scoreData, ?ScoreDE $score = NULL): ScoreDE {
        $score ??= new ScoreDE();
        $score->setStrokes($scoreData['srokes']);

        return $score;
    }

    /**
     * @param string $s packed integer string
     *
     * @return number[] of unpacked integers
     * @noinspection PhpUnusedPrivateMethodInspection*/
    private function unpack(string $s): array {
        $a = array();
        $l = strlen($s);

        for ($i = 0; $i < $l; $i++) {
            $h = substr($s, $i, 1);
            $a[] = hexdec($h);
        }
        return $a;
    }
}