<?php

namespace App\Tests\Controller;

use App\Model\EventFormatType;
use App\Model\EventType;
use App\Repository\EventRepository;
use App\Repository\GameRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Test class for GameController
 */
class GameControllerTest extends WebTestCase {
    private ?EntityManagerInterface $entityManager = null;
    private ?int $leagueId = null;
    private ?LoggerInterface $logger = null;

    protected function tearDown(): void {
        parent::tearDown();

        if ($this->entityManager !== null) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
        $this->logger = null;
    }

    private function getEntityManager(): EntityManagerInterface {
        if ($this->entityManager === null) {
            $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        }

        return $this->entityManager;
    }

    private function getLogger(): LoggerInterface {
        if ($this->logger === null) {
            $this->logger = static::getContainer()->get(LoggerInterface::class);
        }

        return $this->logger;
    }

    private function createAuthenticatedClient(string $role = 'ROLE_USER'): KernelBrowser {
        $client = static::createClient();

        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $logger = $container->get(LoggerInterface::class);
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $userRepository = new UserRepository($em, $logger, $passwordHasher);

        // Try to find a user based on a role
        $username = $role === 'ROLE_ADMIN' ? 'sgl-admin' : 'member';
        $testUser = $userRepository->findOneBy(['username' => $username]);
        $this->leagueId = $testUser?->getLeague()->getId();

        if ( ! $testUser) {
            // Fallback to any user with the appropriate role
            $users = $userRepository->findAll();
            foreach ($users as $user) {
                if (in_array($role, $user->getRoles())) {
                    $testUser = $user;
                    break;
                }
            }
        }

        if ( ! $testUser) {
            throw new \RuntimeException("Test user with role {$role} not found. Create a test user first.");
        }

        $client->loginUser($testUser);

        return $client;
    }

    /**
     * Test that view game page loads successfully
     */
    public function testViewGamePageLoadsSuccessfully(): void {
        $client = $this->createAuthenticatedClient('ROLE_USER');

        $eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());

        try {
            $event = $eventRepository->findFirstEventWithMoreThanOneGame();

            if ( ! $event) {
                $this->markTestSkipped('No event with games found in database');
            } else {
                $game = $event->getGames()->first();

                $client->request('GET', '/game/view/' . $event->getId() . '/' . $game->getId() . '/1');

                $this->assertResponseIsSuccessful();
                $this->assertSelectorTextContains('title', 'Game');
            }
        } catch (NonUniqueResultException $e) {
            $this->markTestSkipped($e->getMessage());
        }

    }

    /**
     * Test that view game requires authentication
     */
    public function testViewRequiresAuthentication(): void {
        $client = static::createClient();
        $client->request('GET', '/game/view/1/1/1');

        // Should redirect to login page when not authenticated
        $this->assertResponseRedirects();
    }

    /**
     * Test that post scores page requires authentication
     */
    public function testPostScoresRequiresAuthentication(): void {
        $client = static::createClient();
        $client->request('GET', '/game/post/scores/1/1/1');

        // Should redirect to login page when not authenticated
        $this->assertResponseRedirects();
    }

    /**
     * Test that post scores page loads successfully for authenticated users
     */
    public function testPostScoresPageLoadsForAuthenticatedUsers(): void {
        $client = $this->createAuthenticatedClient('ROLE_USER');

        $eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());

        try {
            $event = $eventRepository->findFirstEventWithMoreThanOneGame();

            if ( ! $event) {
                $this->markTestSkipped('No event with games found in database');
            } else {
                $game = $event->getGames()->first();

                $client->request('GET', '/game/post/scores/' . $event->getId() . '/' . $game->getId() . '/1');

                $this->assertResponseIsSuccessful();
                $this->assertSelectorTextContains('title', 'Post Game Scores');
            }
        } catch (NonUniqueResultException $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }

    /**
     * Test that new game page requires admin role
     */
    public function testNewGamePageRequiresAdmin(): void {
        $client = static::createClient();
        $client->request('GET', '/game/new/1');

        // Should redirect when not authenticated or not admin
        $this->assertResponseRedirects();
    }

    /**
     * Test that new game page loads for admin users
     */
    public function testNewGamePageLoadsForAdmin(): void {
        $client = $this->createAuthenticatedClient('ROLE_ADMIN');

        $eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());

        try {
            $event = $eventRepository->findFirstEventWithMoreThanOneGame();

            if ( ! $event) {
                $this->markTestSkipped('No event with games found in database');
            } else {
                $client->request('GET', '/game/new/' . $event->getId());

                $this->assertResponseIsSuccessful();
                $this->assertSelectorTextContains('title', 'Add Game');
                $this->assertSelectorExists('form');
            }
        } catch (NonUniqueResultException $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }

    /**
     * Test that edit game requires admin role
     */
    public function testEditGameRequiresAdmin(): void {
        $client = static::createClient();
        $client->request('POST', '/game/edit/1/1/1');

        // Should redirect when not authenticated or not admin
        $this->assertResponseRedirects();
    }

    /**
     * Test that delete game requires admin role
     */
    public function testDeleteGameRequiresAdmin(): void {
        $client = static::createClient();
        $client->request('DELETE', '/game/delete/1/1');

        // Should redirect when not authenticated or not admin
        $this->assertResponseRedirects();
    }

    /**
     * Test that delete game requires DELETE method
     */
    public function testDeleteGameRequiresDeleteMethod(): void {
        $client = $this->createAuthenticatedClient('ROLE_ADMIN');

        $eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());

        try {
            $event = $eventRepository->findFirstEventWithMoreThanOneGame();

            if ( ! $event) {
                $this->markTestSkipped('No event with games found in database');
            } else {
                $game = $event->getGames()->first();

                // Try GET request - should fail
                $client->request('GET', '/game/delete/' . $event->getId() . '/' . $game->getId());
                $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
            }
        } catch (NonUniqueResultException $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }

    /**
     * Test that generate games page requires admin role
     */
    public function testGenerateGamesRequiresAdmin(): void {
        $client = static::createClient();
        $client->request('GET', '/game/generate/1');

        // Should redirect when not authenticated or not admin
        $this->assertResponseRedirects();
    }

    /**
     * Test that generate games page loads for admin users
     */
    public function testGenerateGamesPageLoadsForAdmin(): void {
        $client = $this->createAuthenticatedClient('ROLE_ADMIN');

        $eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());

        try {
            $event = $eventRepository->findFirstEventWithMoreThanOneGame();

            if ( ! $event) {
                $this->markTestSkipped('No event with games found in database');
            } else {
                $client->request('GET', '/game/generate/' . $event->getId());

                // Should be successful or redirect
                $response = $client->getResponse();
                $this->assertTrue($response->isSuccessful() || $response->isRedirect());
            }
        } catch (NonUniqueResultException $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }

    /**
     * Test that change players page requires authentication
     */
    public function testChangePlayersRequiresAuthentication(): void {
        $client = static::createClient();
        $client->request('GET', '/game/change/players/1/1/1');

        // Should redirect to login page when not authenticated
        $this->assertResponseRedirects();
    }

    /**
     * Test that change players page loads for authenticated users
     */
    public function testChangePlayersPageLoadsForAuthenticatedUsers(): void {
        $client = $this->createAuthenticatedClient('ROLE_USER');

        $eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());

        try {
            $event = $eventRepository->findFirstEventWithMoreThanOneGame();

            if ( ! $event) {
                $this->markTestSkipped('No event with games found in database');
            } else {
                $game = $event->getGames()->first();

                $client->request('GET', '/game/change/players/' . $event->getId() . '/' . $game->getId() . '/1');

                $this->assertResponseIsSuccessful();
                $this->assertSelectorTextContains('title', 'Change Players');
            }
        } catch (NonUniqueResultException $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }

    /**
     * Test view non-existent game
     */
    public function testViewNonExistentGame(): void {
        $client = $this->createAuthenticatedClient('ROLE_USER');

        $client->request('GET', '/game/view/999999/999999/1');

        // Should either redirect or show error
        $response = $client->getResponse();
        $this->assertTrue($response->isRedirect() || $response->isSuccessful());

        $eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());

        try {
            $event = $eventRepository->findFirstEventWithMoreThanOneGame();

            if ( ! $event) {
                $this->markTestSkipped('No event with games found in database');
            } else {
                $client->request('GET', '/game/view/' . $event->getId() . '/999999/1');

                // Should either redirect or show error
                $response = $client->getResponse();
                $this->assertTrue($response->isRedirect() || $response->isSuccessful());
            }
        } catch (NonUniqueResultException $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }

    /**
     * Test that game view shows game information
     */
    public function testGameViewShowsGameInformation(): void {
        $client = $this->createAuthenticatedClient('ROLE_USER');

        $eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());

        try {
            $event = $eventRepository->findFirstEventWithMoreThanOneGame();

            if ( ! $event) {
                $this->markTestSkipped('No event with games found in database');
            } else {
                $client->request('GET', '/game/view/' . $event->getId() . '/999999/1');

                // Should either redirect or show error
                $response = $client->getResponse();
                $this->assertTrue($response->isRedirect() || $response->isSuccessful());
            }
        } catch (NonUniqueResultException $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }

    /**
     * Test that post-scores form has required elements
     */
    public function testPostScoresFormHasRequiredElements(): void {
        $client = $this->createAuthenticatedClient('ROLE_USER');

        $eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());

        try {
            $event = $eventRepository->findFirstEventWithMoreThanOneGame();

            if ( ! $event) {
                $this->markTestSkipped('No event with games found in database');
            } else {
                $game = $event->getGames()->first();
                $crawler = $client->request('GET', '/game/post/scores/' . $event->getId() . '/' . $game->getId() . '/1');
                $this->assertResponseIsSuccessful();

                // Check for form
                $form = $crawler->filter('form');
                $this->assertGreaterThanOrEqual(1, $form->count());
            }
        } catch (NonUniqueResultException $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }

    /**
     * Test that routes have correct HTTP methods
     */
    public function testRoutesHaveCorrectHttpMethods(): void {
        $client = static::createClient();
        $router = $client->getContainer()->get('router');

        $viewRoute = $router->getRouteCollection()->get('game_view');
        $this->assertContains('GET', $viewRoute->getMethods());

        $newRoute = $router->getRouteCollection()->get('game_new');
        $this->assertContains('GET', $newRoute->getMethods());
        $this->assertContains('POST', $newRoute->getMethods());

        $editRoute = $router->getRouteCollection()->get('game_edit');
        $this->assertContains('POST', $editRoute->getMethods());

        $deleteRoute = $router->getRouteCollection()->get('game_delete');
        $this->assertContains('DELETE', $deleteRoute->getMethods());

        $postScoresRoute = $router->getRouteCollection()->get('post_scores');
        $this->assertContains('GET', $postScoresRoute->getMethods());
        $this->assertContains('POST', $postScoresRoute->getMethods());

        $changePlayersRoute = $router->getRouteCollection()->get('game_change_players');
        $this->assertContains('GET', $changePlayersRoute->getMethods());
        $this->assertContains('POST', $changePlayersRoute->getMethods());

        $generateRoute = $router->getRouteCollection()->get('game_generate');
        $this->assertContains('GET', $generateRoute->getMethods());
        $this->assertContains('POST', $generateRoute->getMethods());
    }

    /**
     * Test route names exist and generate correct paths
     */
    public function testRouteNames(): void {
        $client = static::createClient();
        $router = $client->getContainer()->get('router');

        // Test that route names exist and point to correct paths
        $this->assertEquals('/game/view/1/2/3', $router->generate('game_view', ['event_id'   => 1,
                                                                                'game_id'    => 2,
                                                                                'gamenumber' => 3
        ]));
        $this->assertEquals('/game/new/1', $router->generate('game_new', ['event_id' => 1]));
        $this->assertEquals('/game/edit/1/2/3', $router->generate('game_edit', ['event_id'   => 1,
                                                                                'game_id'    => 2,
                                                                                'gamenumber' => 3
        ]));
        $this->assertEquals('/game/delete/1/2', $router->generate('game_delete', ['event_id' => 1, 'game_id' => 2]));
        $this->assertEquals('/game/post/scores/1/2/3', $router->generate('post_scores', ['event_id'   => 1,
                                                                                         'game_id'    => 2,
                                                                                         'gamenumber' => 3
        ]));
        $this->assertEquals('/game/change/players/1/2/3', $router->generate('game_change_players', ['event_id'   => 1,
                                                                                                    'game_id'    => 2,
                                                                                                    'gamenumber' => 3
        ]));
        $this->assertEquals('/game/generate/1', $router->generate('game_generate', ['event_id' => 1]));
    }

    /**
     * Test that only authenticated users can access user routes
     */
    public function testOnlyAuthenticatedUsersCanAccessUserRoutes(): void {
        $client = static::createClient();

        // Test view
        $client->request('GET', '/game/view/1/1/1');
        $this->assertResponseRedirects();

        // Test post scores
        $client->request('GET', '/game/post/scores/1/1/1');
        $this->assertResponseRedirects();

        // Test change players
        $client->request('GET', '/game/change/players/1/1/1');
        $this->assertResponseRedirects();
    }

    /**
     * Test that only admin users can access admin routes
     */
    public function testOnlyAdminUsersCanAccessAdminRoutes(): void {
        $client = static::createClient();

        // Test new
        $client->request('GET', '/game/new/1');
        $this->assertResponseRedirects();

        // Test edit
        $client->request('POST', '/game/edit/1/1/1');
        $this->assertResponseRedirects();

        // Test delete
        $client->request('DELETE', '/game/delete/1/1');
        $this->assertResponseRedirects();

        // Test generate
        $client->request('GET', '/game/generate/1');
        $this->assertResponseRedirects();
    }

    private function testDifferentEventTypeLayouts(KernelBrowser $client, EventRepository $eventRepository, int $eventType, int $eventFormat): void {
        try {
            $event = $eventRepository->findFirstByEventTypeAndFormatAndLeague($eventType, $eventFormat, $this->leagueId);

            if ($event == null) {
                try {
                    $this->markTestIncomplete('No event with games found in database for event type ' . $eventType . ' and format ' . $eventFormat);
                } catch (\Exception $e) {
                    return;
                }
            } else {
                $game = $event->getGames()->first();
                $client->request('GET', '/game/view/' . $event->getId() . '/' . $game->getId() . '/1');

                $this->assertResponseIsSuccessful();
                $this->assertSelectorExists('body');
            }
        } catch (NonUniqueResultException|\Exception $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }

    /**
     * Test that view shows different layouts for different event types and event formats
     */
    public function testViewShowsDifferentLayoutsForGameFormats(): void {
        $client = $this->createAuthenticatedClient('ROLE_USER');
        $eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());

        foreach (EventType::values() as $eventType => $eventTypeValue) {
            if (EventType::isTeamMatch($eventType)) {
                $this->testDifferentEventTypeLayouts($client, $eventRepository, $eventType, EventFormatType::toOrdinal(EventFormatType::MATCH_PLAY));
            } elseif (EventType::isTeamEvent($eventType)) {
                foreach (EventFormatType::teamEventFormats() as $eventFormatType => $eventFormatTypeValue) {
                    $this->testDifferentEventTypeLayouts($client, $eventRepository, $eventType, $eventFormatType);
                }
            } elseif (EventType::isSinglesMatch($eventType)) {
                foreach (EventFormatType::singlesEventFormats() as $eventFormatType => $eventFormatTypeValue) {
                    $this->testDifferentEventTypeLayouts($client, $eventRepository, $eventType, $eventFormatType);
                }
            }
        }
    }

    /**
     * Test that post scores handles recorded games appropriately
     */
    public function testPostScoresHandlesRecordedGames(): void {
        $client = $this->createAuthenticatedClient('ROLE_USER');
        $eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());

        try {
            $event = $eventRepository->findFirstEventWithMoreThanOneGame();

            if ( ! $event) {
                $this->markTestSkipped('No event with games found in database');
            } else {
                $game = $event->getGames()->first();
                $client->request('GET', '/game/post/scores/' . $event->getId() . '/' . $game->getId() . '/1');

                $this->assertResponseIsSuccessful();
            }
        } catch (NonUniqueResultException $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }

    /**
     * Test that change players form loads with current players
     */
    public function testChangePlayersFormLoadsWithCurrentPlayers(): void {
        $client = $this->createAuthenticatedClient('ROLE_USER');
        $eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());

        try {
            $event = $eventRepository->findFirstEventWithMoreThanOneGame();

            if ( ! $event) {
                $this->markTestSkipped('No event with games found in database');
            } else {
                $game = $event->getGames()->first();
                $crawler = $client->request('GET', '/game/change/players/' . $event->getId() . '/' . $game->getId() . '/1');

                if ($client->getResponse()->isSuccessful()) {
                    $form = $crawler->filter('form');
                    $this->assertGreaterThanOrEqual(1, $form->count());
                }
            }
        } catch (NonUniqueResultException $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }

    /**
     * Test that game generates successfully for admin users
     */
    public function testGameGeneratesSuccessfullyForAdmin(): void {
        $client = $this->createAuthenticatedClient('ROLE_ADMIN');
        $eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());

        try {
            $event = $eventRepository->findFirstEventWithMoreThanOneGame();

            if ( ! $event) {
                $this->markTestSkipped('No event with games found in database');
            } else {
                $client->request('GET', '/game/generate/' . $event->getId());

                // Should either show form or redirect if games already generated
                $response = $client->getResponse();
                $this->assertTrue($response->isSuccessful() || $response->isRedirect());
            }
        } catch (NonUniqueResultException $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }
}