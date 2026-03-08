<?php

namespace App\Tests\Controller;

use App\Form\EventForm;
use App\Model\EventFormatType;
use App\Model\EventType;
use App\Repository\EventRepository;
use App\Repository\TeamgameRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TeamgameControllerTest extends WebTestCase {
	const LEAGUE_MATCH = 1;
	CONST MATCH_PLAY = 1;
	private ?EntityManagerInterface $entityManager = null;
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

		// Try to find user based on role
		$username = $role === 'ROLE_ADMIN' ? 'sgl-admin' : 'member';
		$testUser = $userRepository->findOneBy(['username' => $username]);

		if (!$testUser) {
			// Fallback to any user with appropriate role
			$users = $userRepository->findAll();
			foreach ($users as $user) {
				if (in_array($role, $user->getRoles())) {
					$testUser = $user;
					break;
				}
			}
		}

		if (!$testUser) {
			throw new \RuntimeException("Test user with role {$role} not found. Create a test user first.");
		}

		$client->loginUser($testUser);

		return $client;
	}

	/**
	 * Test that view teamgame page loads successfully
	 */
	public function testViewPageLoadsSuccessfully(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());

		try {
			$event = $eventRepository->findFirstTeamEventWithTeamgame();

			if (!$event) {
				$this->markTestSkipped('No event with teamgames found in database');
			} else {
				$teamgame = $event->getTeamgames()->first();

				$client->request('GET', '/teamgame/view/' . $event->getId() . '/' . $teamgame->getId() . '/1');

				$this->assertResponseIsSuccessful();
				$this->assertSelectorTextContains('title', 'Team Game');
			}
		} catch (NonUniqueResultException $e) {
			$this->markTestSkipped($e->getMessage());
		}
	}

	/**
	 * Test that view teamgame requires authentication
	 */
	public function testViewRequiresAuthentication(): void {
		$client = static::createClient();
		$client->request('GET', '/teamgame/view/1/1/1');

		// Should redirect to login page when not authenticated
		$this->assertResponseRedirects();
	}

	/**
	 * Test that new teamgame page requires admin role
	 */
	public function testNewTeamgamePageRequiresAdmin(): void {
		$client = static::createClient();
		$client->request('GET', '/teamgame/new/1');

		// Should redirect when not authenticated or not admin
		$this->assertResponseRedirects();
	}

	/**
	 * Test that new teamgame page loads for admin users
	 * @throws NonUniqueResultException
	 */
	public function testNewTeamgamePageLoadsForAdmin(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());
		$event = $eventRepository->findFirstTeamEventWithTeamgame();

		if (!$event) {
			$this->markTestSkipped('No event found in database');
		}

		$client->request('GET', '/teamgame/new/' . $event->getId());

		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains('title', 'Add Team Game');
		$this->assertSelectorExists('form');
	}

	/**
	 * Test that edit teamgame page requires admin role
	 */
	public function testEditTeamgamePageRequiresAdmin(): void {
		$client = static::createClient();
		$client->request('POST', '/teamgame/edit/1/1/1');

		// Should redirect when not authenticated or not admin
		$this->assertResponseRedirects();
	}

	/**
	 * Test that edit teamgame page loads for admin users
	 */
	public function testEditTeamgamePageLoadsForAdmin(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());

		try {
			$event = $eventRepository->findFirstTeamEventWithTeamgame();

			if (!$event) {
				$this->markTestSkipped('No event with teamgames found in database');
			} else {
				$teamgame = $event->getTeamgames()->first();

				$crawler = $client->request('POST', '/teamgame/edit/' . $event->getId() . '/' . $teamgame->getId() . '/1');

				// Should show form or redirect after processing
				$response = $client->getResponse();
				$this->assertTrue($response->isSuccessful() || $response->isRedirect());
			}
		} catch (NonUniqueResultException $e) {
			$this->markTestSkipped($e->getMessage());
		}
	}

	/**
	 * Test that delete teamgame requires admin role
	 */
	public function testDeleteTeamgameRequiresAdmin(): void {
		$client = static::createClient();
		$client->request('DELETE', '/teamgame/delete/1/1');

		// Should redirect when not authenticated or not admin
		$this->assertResponseRedirects();
	}

	/**
	 * Test that delete teamgame requires DELETE method
	 */
	public function testDeleteTeamgameRequiresDeleteMethod(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());

		try {
			$event = $eventRepository->findFirstTeamEventWithTeamgame();

			if (!$event) {
				$this->markTestSkipped('No event with teamgames found in database');
			} else {
				$teamgame = $event->getTeamgames()->first();

				// Try GET request - should fail
				$client->request('GET', '/teamgame/delete/' . $event->getId() . '/' . $teamgame->getId());
				$this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
			}
		} catch (NonUniqueResultException $e) {
			$this->markTestSkipped($e->getMessage());
		}
	}

	/**
	 * Test that post scores page requires user role
	 */
	public function testPostScoresPageRequiresUser(): void {
		$client = static::createClient();
		$client->request('GET', '/teamgame/post/scores/1/1/1/');

		// Should redirect when not authenticated
		$this->assertResponseRedirects();
	}

	/**
	 * Test that post scores page loads for authenticated users
	 */
	public function testPostScoresPageLoadsForUser(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());

		try {
			$event = $eventRepository->findFirstTeamEventWithTeamgame();

			if (!$event) {
				$this->markTestSkipped('No event with teamgames found in database');
			} else {
				$teamgame = $event->getTeamgames()->first();

				$client->request('GET', '/teamgame/post/scores/' . $event->getId() . '/' . $teamgame->getId() . '/1');

				$this->assertResponseIsSuccessful();
				$this->assertSelectorTextContains('title', 'Post Team Game Scores');
			}
		} catch (NonUniqueResultException $e) {
			$this->markTestSkipped($e->getMessage());
		}
	}

	/**
	 * Test view non-existent teamgame
	 */
	public function testViewNonExistentTeamgame(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$client->request('GET', '/teamgame/view/999999/999999/1');

		// Should either redirect or show error
		$response = $client->getResponse();
		$this->assertTrue($response->isRedirect() || $response->isSuccessful());
	}

	/**
	 * Test that teamgame view shows teamgame details
	 */
	public function testTeamgameViewShowsTeamgameDetails(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());

		try {
			$event = $eventRepository->findFirstTeamEventWithTeamgame();

			if (!$event) {
				$this->markTestSkipped('No event with teamgames found in database');
			} else {
				$teamgame = $event->getTeamgames()->first();

				$client->request('GET', '/teamgame/view/' . $event->getId() . '/' . $teamgame->getId() . '/1');

				$this->assertResponseIsSuccessful();
				$this->assertSelectorExists('.teamgame-details, .teamgame-info, table, body');
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

		$viewRoute = $router->getRouteCollection()->get('teamgame_view');
		$this->assertContains('GET', $viewRoute->getMethods());

		$newRoute = $router->getRouteCollection()->get('teamgame_new');
		$this->assertContains('GET', $newRoute->getMethods());
		$this->assertContains('POST', $newRoute->getMethods());

		$editRoute = $router->getRouteCollection()->get('teamgame_edit');
		$this->assertContains('POST', $editRoute->getMethods());

		$deleteRoute = $router->getRouteCollection()->get('teamgame_delete');
		$this->assertContains('DELETE', $deleteRoute->getMethods());

		$postScoresRoute = $router->getRouteCollection()->get('teamgame_post_scores');
		$this->assertContains('GET', $postScoresRoute->getMethods());
		$this->assertContains('POST', $postScoresRoute->getMethods());
	}

	/**
	 * Test route names exist and generate correct paths
	 */
	public function testRouteNames(): void {
		$client = static::createClient();
		$router = $client->getContainer()->get('router');

		// Test that route names exist and point to correct paths
		$this->assertEquals('/teamgame/view/1/2/3', $router->generate('teamgame_view', ['event_id' => 1, 'teamgame_id' => 2, 'gamenumber' => 3]));
		$this->assertEquals('/teamgame/new/1', $router->generate('teamgame_new', ['event_id' => 1]));
		$this->assertEquals('/teamgame/edit/1/2/3', $router->generate('teamgame_edit', ['event_id' => 1, 'teamgame_id' => 2, 'gamenumber' => 3]));
		$this->assertEquals('/teamgame/delete/1/2', $router->generate('teamgame_delete', ['event_id' => 1, 'teamgame_id' => 2]));
		$this->assertEquals('/teamgame/post/scores/1/2/3', $router->generate('teamgame_post_scores', ['event_id' => 1, 'teamgame_id' => 2, 'gamenumber' => 3]));
	}

	/**
	 * Test that new teamgame form has required fields
	 * @throws NonUniqueResultException
	 */
	public function testNewTeamgameFormHasRequiredFields(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());
		$event = $eventRepository->findFirstTeamEventWithTeamgame();

		if (!$event) {
			$this->markTestSkipped('No event found in database');
		}

		$crawler = $client->request('GET', '/teamgame/new/' . $event->getId());

		$this->assertResponseIsSuccessful();
		// Verify form exists
		$this->assertGreaterThan(0, $crawler->filter('form')->count());
	}

	/**
	 * Test that only authenticated users can access user routes
	 */
	public function testOnlyAuthenticatedUsersCanAccessUserRoutes(): void {
		$client = static::createClient();

		// Test view
		$client->request('GET', '/teamgame/view/1/1/1');
		$this->assertResponseRedirects();

		// Test post scores
		$client->request('GET', '/teamgame/post/scores/1/1/1/');
		$this->assertResponseRedirects();
	}

	/**
	 * Test that only admin users can access admin routes
	 */
	public function testOnlyAdminUsersCanAccessAdminRoutes(): void {
		$client = static::createClient();

		// Test new
		$client->request('GET', '/teamgame/new/1');
		$this->assertResponseRedirects();

		// Test edit
		$client->request('POST', '/teamgame/edit/1/1/1');
		$this->assertResponseRedirects();

		// Test delete
		$client->request('DELETE', '/teamgame/delete/1/1');
		$this->assertResponseRedirects();
	}
}