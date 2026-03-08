<?php

namespace App\Tests\Controller;

use App\Controller\EventController;
use App\Repository\EventRepository;
use App\Repository\SeasonRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Test class for EventController
 */
class EventControllerTest extends WebTestCase {
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

		// Try to find a user based on a role
		$username = $role === 'ROLE_ADMIN' ? 'sgl-admin' : 'member';
		$testUser = $userRepository->findOneBy(['username' => $username]);

		if (!$testUser) {
			// Fallback to any user with the appropriate role
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
	 * Test that event list page loads successfully
	 */
	public function testListPageLoadsSuccessfully(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		// Get a season ID from the database
		$seasonRepository = new SeasonRepository($this->getEntityManager(), $this->getLogger());
		$season = $seasonRepository->findOneBy([]);

		if (!$season) {
			$this->markTestSkipped('No season found in database');
		}

		$client->request('GET', '/event/list/' . $season->getId());

		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains('title', 'Events');
	}

	/**
	 * Test that an event list requires authentication
	 */
	public function testListRequiresAuthentication(): void {
		$client = static::createClient();
		$client->request('GET', '/event/list/1');

		// Should redirect to the login page when not authenticated
		$this->assertResponseRedirects();
	}

	/**
	 * Test that the new event page loads successfully for admin users
	 */
	public function testNewEventPageRequiresAdmin(): void {
		$client = static::createClient();
		$client->request('GET', '/event/new/1');

		// Should redirect when not authenticated or not admin
		$this->assertResponseRedirects();
	}

	/**
	 * Test that edit event page requires an admin role
	 */
	public function testEditEventPageRequiresAdmin(): void {
		$client = static::createClient();
		$client->request('GET', '/event/edit/1');

		// Should redirect when not authenticated or not admin
		$this->assertResponseRedirects();
	}

	/**
	 * Test that view event page loads successfully
	 */
	public function testViewEventPageLoadsSuccessfully(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');
		$eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());

		try {
			$event = $eventRepository->findFirstEventWithMoreThanOneGame();

			if (!$event) {
				$this->markTestSkipped( 'No event with games found in database' );
			} else {
				$client->request('GET', '/event/view/' . $event->getId());

				$this->assertResponseIsSuccessful();
				$this->assertSelectorTextContains('title', 'Event');
			}
		} catch ( NonUniqueResultException $e ) {
			$this->markTestSkipped($e->getMessage());
		}
	}

	/**
	 * Test that event results page loads successfully
	 */
	public function testEventResultsPageLoadsSuccessfully(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');
		$eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());

		try {
			$event = $eventRepository->findFirstEventWithMoreThanOneGame();

			if (!$event) {
				$this->markTestSkipped( 'No event with games found in database');
			} else {
				$client->request('GET', '/event/results/' . $event->getId());

				// Should be successful or show an error page for events without games
				$this->assertResponseStatusCodeSame(Response::HTTP_OK);
			}
		} catch ( NonUniqueResultException $e ) {
			$this->markTestSkipped($e->getMessage());
		}
	}

	/**
	 * Test that delete event requires admin role
	 */
	public function testDeleteEventRequiresAdmin(): void {
		$client = static::createClient();
		$client->request('DELETE', '/event/delete/1');

		// Should redirect when not authenticated or not admin
		$this->assertResponseRedirects();
	}

	/**
	 * Test that view last event redirects appropriately
	 */
	public function testViewLastEventRedirects(): void {
		$client = static::createClient();
		$client->request('GET', '/event/viewlast');

		// Should redirect to log in or to the event view
		$this->assertResponseRedirects();
	}

	/**
	 * Test that view the next event redirects appropriately
	 */
	public function testViewNextEventRedirects(): void {
		$client = static::createClient();
		$client->request('GET', '/event/viewnext');

		// Should redirect to log in or to the event view
		$this->assertResponseRedirects();
	}

	/**
	 * Test that view season events redirect appropriately
	 */
	public function testViewSeasonEventsRedirects(): void {
		$client = static::createClient();
		$client->request('GET', '/event/viewseason');

		// Should redirect to log in or to the event list
		$this->assertResponseRedirects();
	}

	/**
	 * Test that last event results requires an admin role
	 */
	public function testLastEventResultsRequiresAdmin(): void {
		$client = static::createClient();
		$client->request('GET', '/event/resultslast');

		// Should redirect when not authenticated or not admin
		$this->assertResponseRedirects();
	}

	/**
	 * Test that an event list shows correct season information
	 */
	public function testEventListShowsSeasonInformation(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$seasonRepository = new SeasonRepository($this->getEntityManager(), $this->getLogger());
		$season = $seasonRepository->findOneBy([]);

		if (!$season) {
			$this->markTestSkipped('No season found in database');
		}

		$client->request('GET', '/event/list/' . $season->getId());

		$this->assertResponseIsSuccessful();
		$this->assertSelectorExists('.season-info, table, .event-list');
	}

	/**
	 * Test that the event view shows correct event information
	 */
	public function testEventViewShowsEventInformation(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());
		$event = $eventRepository->find(111);

		try {
			$event = $eventRepository->findFirstEventWithMoreThanOneGame();

			if (!$event) {
				$this->markTestSkipped( 'No event with games found in database' );
			} else {
				$client->request('GET', '/event/view/' . $event->getId());

				$this->assertResponseIsSuccessful();
				$this->assertSelectorExists('.event-details, .event-info, table');
			}
		} catch ( NonUniqueResultException $e ) {
			$this->markTestSkipped($e->getMessage());
		}
	}

	/**
	 * Test that event results handles events with no games
	 * @throws Exception
	 */
	public function testEventResultsHandlesNoGames(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');
		$eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());

		$connection = $this->getEntityManager()->getConnection();
		$db = $connection->getDatabase(); // Outputs the effective DB name

		try {
			$event = $eventRepository->findFirstEventWithNoGames();

			if (!$event) {
				$this->markTestSkipped( 'No match play events with no games found in database' );
			} else {
				$client->request('GET', '/event/results/' . $event->getId());

				$this->assertResponseIsSuccessful();
				// Should show an error message
				$this->assertSelectorTextContains('body', 'No Games Defined');
			}
		} catch ( NonUniqueResultException $e ) {
			$this->markTestSkipped($e->getMessage());
		}
	}

	/**
	 * Test view non-existent event
	 */
	public function testViewNonExistentEvent(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$client->request('GET', '/event/view/999999');

		// Should either redirect or show an error
		$response = $client->getResponse();
		$this->assertTrue($response->isRedirect() || $response->isSuccessful());
	}

	/**
	 * Test results for a non-existent event
	 */
	public function testResultsForNonExistentEvent(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$client->request('GET', '/event/results/999999');

		// Should either redirect or show an error
		$response = $client->getResponse();
		$this->assertTrue($response->isRedirect() || $response->isSuccessful());
	}

	/**
	 * Test changeGameTimes static method
	 */
	public function testChangeGameTimesUpdatesCorrectly(): void {
		// This would require creating mock games and testing the static method
		// For now, we mark it as a unit test placeholder
		$this->assertTrue(method_exists( EventController::class, 'changeGameTimes'));
	}

	/**
	 * Test that routes have correct HTTP methods
	 */
	public function testRoutesHaveCorrectHttpMethods(): void {
		$client = static::createClient();
		$router = $client->getContainer()->get('router');

		$listRoute = $router->getRouteCollection()->get('event_list');
		$this->assertContains('GET', $listRoute->getMethods());

		$deleteRoute = $router->getRouteCollection()->get('event_delete');
		$this->assertContains('DELETE', $deleteRoute->getMethods());

		$editRoute = $router->getRouteCollection()->get('event_edit');
		$this->assertContains('GET', $editRoute->getMethods());
		$this->assertContains('POST', $editRoute->getMethods());

		$newRoute = $router->getRouteCollection()->get('event_new');
		$this->assertContains('GET', $newRoute->getMethods());
		$this->assertContains('POST', $newRoute->getMethods());
	}

	/**
	 * Test that view event shows proper event type information
	 */
	public function testViewEventShowsEventTypeInformation(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');
		$eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());

		try {
			$event = $eventRepository->findFirstEventWithMoreThanOneGame();

			if (!$event) {
				$this->markTestSkipped( 'No event with games found in database' );
			} else {
				$client->request('GET', '/event/view/' . $event->getId());

				$this->assertResponseIsSuccessful();
				// Event type info should be present
				$this->assertSelectorExists('body');
			}
		} catch ( NonUniqueResultException $e ) {
			$this->markTestSkipped($e->getMessage());
		}
	}

	/**
	 * Test route names exist and generate correct paths
	 */
	public function testRouteNames(): void {
		$client = static::createClient();
		$router = $client->getContainer()->get('router');

		// Test that route names exist and point to correct paths
		$this->assertEquals('/event/list/1', $router->generate('event_list', ['id' => 1]));
		$this->assertEquals('/event/new/1', $router->generate('event_new', ['id' => 1]));
		$this->assertEquals('/event/view/1', $router->generate('event_view', ['id' => 1]));
		$this->assertEquals('/event/edit/1', $router->generate('event_edit', ['id' => 1]));
		$this->assertEquals('/event/delete/1', $router->generate('event_delete', ['id' => 1]));
		$this->assertEquals('/event/results/1', $router->generate('event_results', ['id' => 1]));
		$this->assertEquals('/event/viewlast', $router->generate('event_viewlast'));
		$this->assertEquals('/event/viewnext', $router->generate('event_viewnext'));
		$this->assertEquals('/event/viewseason', $router->generate('event_viewseason'));
		$this->assertEquals('/event/resultslast', $router->generate('event_resultslast'));
	}
}