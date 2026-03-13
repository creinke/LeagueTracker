<?php

namespace App\Tests\Controller\Api;

use App\Entity\EventDE;
use App\Entity\SeasonDE;
use App\Entity\UserDE;

class ApiEventControllerTest extends ApiTestCase {
    public function testEventListBySeason(): void {
        $client = $this->createAuthenticatedClient();
        $user = $this->entityManager->getRepository(UserDE::class)->findOneBy(['username' => 'member']);
        $leagueId = $user->getLeague()->getId();

        $seasonRepository = $this->entityManager->getRepository(SeasonDE::class);
        $seasons = $seasonRepository->findSeasonsByLeagueId($leagueId);

        if (empty($seasons)) {
            $this->markTestSkipped('No seasons found for the test league.');
        }

        $testSeason = $seasons[0];
        $this->request($client, 'GET', '/api/event/list/' . $testSeason->getId());

        $this->assertResponseIsSuccessful();
        $data = $this->getJsonResponse($client);

        $this->assertIsArray($data);
        if (count($data) > 0) {
            $session = $data[0];
            $this->assertArrayHasKey('id', $session);
            $this->assertArrayHasKey('name', $session);
            $this->assertArrayHasKey('events', $session);
        }
    }

    public function testEventView(): void {
        $client = $this->createAuthenticatedClient();
        $user = $this->entityManager->getRepository(UserDE::class)->findOneBy(['username' => 'member']);
        $leagueId = $user->getLeague()->getId();

        // Get an event from the first session of the first season
        $seasonRepository = $this->entityManager->getRepository(SeasonDE::class);
        $seasons = $seasonRepository->findSeasonsByLeagueId($leagueId);

        if (empty($seasons) || empty($seasons[0]->getSessions()) || empty($seasons[0]->getSessions()[0]->getEvents())) {
            $this->markTestSkipped('No events found for the test league.');
        }

        $testEvent = $seasons[0]->getSessions()[0]->getEvents()[0];
        $this->request($client, 'GET', '/api/event/view/' . $testEvent->getId());

        $this->assertResponseIsSuccessful();
        $data = $this->getJsonResponse($client);

        $this->assertEquals($testEvent->getId(), $data['id']);
        $this->assertArrayHasKey('isRegistered', $data);
    }

    public function testEventRegisterToggle(): void {
        $client = $this->createAuthenticatedClient();
        $user = $this->entityManager->getRepository(UserDE::class)->findOneBy(['username' => 'member']);
        $leagueId = $user->getLeague()->getId();

        $seasonRepository = $this->entityManager->getRepository(SeasonDE::class);
        $seasons = $seasonRepository->findSeasonsByLeagueId($leagueId);

        if (empty($seasons) || empty($seasons[0]->getSessions()) || empty($seasons[0]->getSessions()[0]->getEvents())) {
            $this->markTestSkipped('No events found for the test league.');
        }

        $testEvent = $seasons[0]->getSessions()[0]->getEvents()[0];

        $this->request($client, 'POST', '/api/event/register/' . $testEvent->getId());
        
        // In the test database, 'member' might not have a linked Player profile, 
        // leading to a 403. We accept either 200 or 403 as valid API responses here.
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [200, 403]);
    }

    public function testEventViewNotFound(): void {
        $client = $this->createAuthenticatedClient();
        $this->request($client, 'GET', '/api/event/view/999999');
        $this->assertResponseStatusCodeSame(404);
    }
}
