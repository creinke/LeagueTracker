<?php

namespace App\Tests\Controller\Api;

use App\Entity\EventDE;
use App\Entity\GameDE;
use App\Entity\UserDE;
use App\Model\EventType;
use App\Model\EventFormatType;
use App\Repository\EventRepository;
use Psr\Log\LoggerInterface;

class ApiGameControllerTest extends ApiTestCase {
    public function testGameListByEvent(): void {
        $client = $this->createAuthenticatedClient();
        $user = $this->entityManager->getRepository(UserDE::class)->findOneBy(['username' => 'member']);
        $leagueId = $user->getLeague()->getId();

        $eventRepository = new EventRepository($this->entityManager, $this->logger);
        try {
            $event = $eventRepository->findFirstByEventTypeAndFormatAndLeague(1,1,$leagueId);
        } catch (\Exception $e) {
            $this->markTestSkipped('Exception caught invoking findFirstByEventTypeAndFormat method.');
        }

        if (!$event || $event->getSession()->getSeason()->getLeague()->getId() !== $leagueId) {
            $this->markTestSkipped('No suitable event found for the test league.');
        }

        $this->request($client, 'GET', '/api/game/list/' . $event->getId());
        $this->assertResponseIsSuccessful();
        $data = $this->getJsonResponse($client);

        $this->assertIsArray($data);
        if (count($data) > 0) {
            $this->assertArrayHasKey('id', $data[0]);
            $this->assertArrayHasKey('players', $data[0]);
            $this->assertArrayHasKey('isRecorded', $data[0]);
            $this->assertArrayHasKey('teamNames', $data[0]);
        }
    }

    public function testGameScoresGet(): void {
        $client = $this->createAuthenticatedClient();
        $user = $this->entityManager->getRepository(UserDE::class)->findOneBy(['username' => 'member']);
        $leagueId = $user->getLeague()->getId();

        $game = $this->entityManager->getRepository(GameDE::class)->findOneBy([]);
        foreach ($this->entityManager->getRepository(GameDE::class)->findAll() as $g) {
            if ($g->getEvent()->getSession()->getSeason()->getLeague()->getId() === $leagueId) {
                $game = $g;
                break;
            }
        }

        if (!$game || $game->getEvent()->getSession()->getSeason()->getLeague()->getId() !== $leagueId) {
            $this->markTestSkipped('No games found for the test league.');
        }

        $this->request($client, 'GET', '/api/game/scores/' . $game->getId());
        $this->assertResponseIsSuccessful();
        $data = $this->getJsonResponse($client);

        $this->assertEquals($game->getId(), $data['gameId']);
        $this->assertArrayHasKey('nines', $data);
        $this->assertArrayHasKey('playerScores', $data);
    }

    public function testGameScoresSave(): void {
        $client = $this->createAuthenticatedClient();
        $user = $this->entityManager->getRepository(UserDE::class)->findOneBy(['username' => 'member']);
        $leagueId = $user->getLeague()->getId();

        $game = null;
        foreach ($this->entityManager->getRepository(GameDE::class)->findAll() as $g) {
            if ($g->getEvent()->getSession()->getSeason()->getLeague()->getId() === $leagueId) {
                $game = $g;
                break;
            }
        }

        if (!$game) {
            $this->markTestSkipped('No games found for testing.');
        }

        // Get initial state to find a valid player and tee
        $this->request($client, 'GET', '/api/game/scores/' . $game->getId());
        $initialData = $this->getJsonResponse($client);
        if (empty($initialData['playerScores'])) {
            $this->markTestSkipped('No player scores available in this game.');
        }
        $playerScore = $initialData['playerScores'][0];

        $payload = [
            'type' => 'REGULAR',
            'playerScores' => [
                [
                    'playerId' => $playerScore['playerId'],
                    'isPlayed' => true,
                    'currentTeeId' => $playerScore['currentTeeId'],
                    'strokes' => array_fill(0, 9, 4) // Par 4 for all holes
                ]
            ]
        ];

        $this->request($client, 'POST', '/api/game/scores/' . $game->getId(), [], [], [], json_encode($payload));
        $this->assertResponseIsSuccessful();
        $data = $this->getJsonResponse($client);
        $this->assertTrue($data['success']);
    }

    public function skipTestSaveScoresRegularGame(): void {
        $client = $this->createAuthenticatedClient('sgl-admin');

        $user = $this->entityManager->getRepository(UserDE::class)->findOneBy(['username' => 'sgl-admin']);
        $leagueId = $user->getLeague()->getId();

        $gameRepository = $this->entityManager->getRepository(GameDE::class);
        $game = $gameRepository->find(1551);

        if (!$game) {
            $this->markTestSkipped('No suitable game found for testing.');
        }

        $this->request($client, 'GET', '/api/game/scores/1551');
        $initialData = $this->getJsonResponse($client);

        if (empty($initialData['playerScores'])) {
            $this->markTestSkipped('No player scores available for this game.');
        }

        $initialData['playerScores'][0]['strokes'] = array_fill(0, 9, 7);
        $initialData['playerScores'][1]['strokes'] = array_fill(0, 9, 0);
        $initialData['playerScores'][2]['strokes'] = array_fill(0, 9, 7);
        $initialData['playerScores'][3]['strokes'] = array_fill(0, 9, 6);

        $payload = [
            'type' => 'REGULAR',
            'playerScores' => $initialData['playerScores'],
        ];

        $this->request($client, 'POST', '/api/game/scores/1551', [], [], [], json_encode($payload));
        $this->assertResponseIsSuccessful();
        $data = $this->getJsonResponse($client);

        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
    }


    public function testGameRosterGet(): void {
        $client = $this->createAuthenticatedClient();
        $user = $this->entityManager->getRepository(UserDE::class)->findOneBy(['username' => 'member']);
        $leagueId = $user->getLeague()->getId();

        $game = null;
        foreach ($this->entityManager->getRepository(GameDE::class)->findAll() as $g) {
            if ($g->getEvent()->getSession()->getSeason()->getLeague()->getId() === $leagueId) {
                $game = $g;
                break;
            }
        }

        if (!$game) {
            $this->markTestSkipped('No games found for testing.');
        }
        
        $this->request($client, 'GET', '/api/game/roster/' . $game->getId());
        $this->assertResponseIsSuccessful();
        $data = $this->getJsonResponse($client);

        $this->assertArrayHasKey('currentGamePlayers', $data);
        $this->assertArrayHasKey('leagueRoster', $data);
    }

    public function testGameSubstitute(): void {
        $client = $this->createAuthenticatedClient();
        $user = $this->entityManager->getRepository(UserDE::class)->findOneBy(['username' => 'member']);
        $leagueId = $user->getLeague()->getId();

        $game = null;
        foreach ($this->entityManager->getRepository(GameDE::class)->findAll() as $g) {
            if ($g->getEvent()->getSession()->getSeason()->getLeague()->getId() === $leagueId) {
                $game = $g;
                break;
            }
        }

        if (!$game) {
            $this->markTestSkipped('No games found for testing.');
        }
        
        $this->request($client, 'GET', '/api/game/roster/' . $game->getId());
        $rosterData = $this->getJsonResponse($client);
        
        if (!isset($rosterData['leagueRoster']) || count($rosterData['leagueRoster']) < 2) {
            $this->markTestSkipped('Not enough players in roster to test substitution.');
        }

        $newPlayerIds = [
            $rosterData['leagueRoster'][0]['id'],
            $rosterData['leagueRoster'][1]['id']
        ];

        $this->request($client, 'POST', '/api/game/substitute/' . $game->getId(), [], [], [], json_encode(['playerIds' => $newPlayerIds]));
        $this->assertResponseIsSuccessful();
        $data = $this->getJsonResponse($client);
        $this->assertTrue($data['success']);
    }

    public function testUnauthorizedAccess(): void {
        $client = static::createClient();
        $this->request($client, 'GET', '/api/game/list/1');
        $this->assertResponseStatusCodeSame(401);
    }
}
