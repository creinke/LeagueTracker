<?php

namespace App\Tests\Controller\Api;

use App\Entity\PlayerDE;
use App\Repository\PlayerRepository;

class ApiPlayerControllerTest extends ApiTestCase {
    public function testPlayerList(): void {
        $client = $this->createAuthenticatedClient();
        $user = $this->entityManager->getRepository(\App\Entity\UserDE::class)->findOneBy(['username' => 'member']);
        $leagueId = $user->getLeague()->getId();

        $this->request($client, 'GET', '/api/player/list');

        $this->assertResponseIsSuccessful();
        $data = $this->getJsonResponse($client);

        $this->assertIsArray($data);
        if (count($data) > 0) {
            $player = $data[0];
            $this->assertArrayHasKey('id', $player);
            $this->assertArrayHasKey('firstname', $player);
            $this->assertArrayHasKey('lastname', $player);
            $this->assertArrayHasKey('isDefunct', $player);
            $this->assertArrayHasKey('seedHandicapIndex', $player);
        }
    }

    public function testPlayerView(): void {
        $client = $this->createAuthenticatedClient();
        $user = $this->entityManager->getRepository(\App\Entity\UserDE::class)->findOneBy(['username' => 'member']);
        $leagueId = $user->getLeague()->getId();

        $playerRepository = $this->entityManager->getRepository(\App\Entity\PlayerDE::class);
        $players = $playerRepository->findAllPlayers($leagueId);

        if (empty($players)) {
            $this->markTestSkipped('No players found for the test league.');
        }

        $testPlayer = $players[0];
        $this->request($client, 'GET', '/api/player/view/' . $testPlayer->getId());

        $this->assertResponseIsSuccessful();
        $data = $this->getJsonResponse($client);

        $this->assertEquals($testPlayer->getId(), $data['id']);
        $this->assertEquals($testPlayer->getFirstname(), $data['firstname']);
        $this->assertEquals($testPlayer->getLastname(), $data['lastname']);
    }

    public function testPlayerViewNotFound(): void {
        $client = $this->createAuthenticatedClient();

        $this->request($client, 'GET', '/api/player/view/999999');

        $this->assertResponseStatusCodeSame(404);
        $data = $this->getJsonResponse($client);
        $this->assertArrayHasKey('error', $data);
    }

    public function testPlayerListUnauthorized(): void {
        $client = static::createClient();
        $client->request('GET', '/api/player/list');

        $this->assertResponseStatusCodeSame(401);
    }
}
