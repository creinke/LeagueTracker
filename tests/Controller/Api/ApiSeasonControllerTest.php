<?php

namespace App\Tests\Controller\Api;

use App\Entity\SeasonDE;

class ApiSeasonControllerTest extends ApiTestCase {
    public function testSeasonList(): void {
        $client = $this->createAuthenticatedClient();

        $this->request($client, 'GET', '/api/season/list');

        $this->assertResponseIsSuccessful();
        $data = $this->getJsonResponse($client);

        $this->assertIsArray($data);
        if (count($data) > 0) {
            $season = $data[0];
            $this->assertArrayHasKey('id', $season);
            $this->assertArrayHasKey('name', $season);
            $this->assertArrayHasKey('startDate', $season);
            $this->assertArrayHasKey('endDate', $season);
        }
    }

    public function testSeasonView(): void {
        $client = $this->createAuthenticatedClient();
        $user = $this->entityManager->getRepository(\App\Entity\UserDE::class)->findOneBy(['username' => 'member']);
        $leagueId = $user->getLeague()->getId();

        $seasonRepository = $this->entityManager->getRepository(SeasonDE::class);
        $seasons = $seasonRepository->findSeasonsByLeagueId($leagueId);

        if (empty($seasons)) {
            $this->markTestSkipped('No seasons found for the test league.');
        }

        $testSeason = $seasons[0];
        $this->request($client, 'GET', '/api/season/view/' . $testSeason->getId());

        $this->assertResponseIsSuccessful();
        $data = $this->getJsonResponse($client);

        $this->assertEquals($testSeason->getId(), $data['id']);
        $this->assertEquals($testSeason->getName(), $data['name']);
        $this->assertArrayHasKey('sessions', $data);
    }

    public function testSeasonViewNotFound(): void {
        $client = $this->createAuthenticatedClient();

        $this->request($client, 'GET', '/api/season/view/999999');

        $this->assertResponseStatusCodeSame(404);
    }
}
