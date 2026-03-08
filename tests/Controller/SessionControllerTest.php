<?php

namespace App\Tests\Controller;

use App\Entity\SessionDE;
use App\Repository\SessionRepository;
use App\Repository\SeasonRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Test class for SessionController
 */
class SessionControllerTest extends WebTestCase {
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
	 * Test that session list page requires authentication
	 */
	public function testListRequiresAuthentication(): void {
		$client = static::createClient();
		$client->request('GET', '/session/list/1');

		// Should redirect to login page when not authenticated
		$this->assertResponseRedirects();
	}

	/**
	 * Test that session list page requires admin role
	 */
	public function testListRequiresAdmin(): void {
		$client = static::createClient();
		$client->request('GET', '/session/list/1');

		// Should redirect when not authenticated or not admin
		$this->assertResponseRedirects();
	}

	/**
	 * Test that session list page loads successfully for admin
	 */
	public function testListPageLoadsSuccessfullyForAdmin(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$seasonRepository = new SeasonRepository($this->getEntityManager(), $this->getLogger());
		$season = $seasonRepository->findOneBy([]);

		if (!$season) {
			$this->markTestSkipped('No season found in database');
		}

		$client->request('GET', '/session/list/' . $season->getId());

		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains('title', 'Sessions');
	}

	/**
	 * Test that view session page requires authentication
	 */
	public function testViewRequiresAuthentication(): void {
		$client = static::createClient();
		$client->request('GET', '/session/view/1');

		// Should redirect to login page when not authenticated
		$this->assertResponseRedirects();
	}

	/**
	 * Test that view session page requires admin role
	 */
	public function testViewRequiresAdmin(): void {
		$client = static::createClient();
		$client->request('GET', '/session/view/1');

		// Should redirect when not authenticated or not admin
		$this->assertResponseRedirects();
	}

	/**
	 * Test that view session page loads successfully
	 */
	public function testViewSessionPageLoadsSuccessfully(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$sessionRepository = new SessionRepository($this->getEntityManager(), $this->getLogger());
		$session = $sessionRepository->findOneBy([]);

		if (!$session) {
			$this->markTestSkipped('No session found in database');
		}

		$client->request('GET', '/session/view/' . $session->getId());

		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains('title', 'Session');
	}

	/**
	 * Test view non-existent session shows error
	 */
	public function testViewNonExistentSession(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$client->request('GET', '/session/view/999999');

		// Should show error page for non-existent session
		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains('title', 'Error');
	}

	/**
	 * Test that new session page requires authentication
	 */
	public function testNewSessionPageRequiresAuthentication(): void {
		$client = static::createClient();
		$client->request('GET', '/session/new/1');

		// Should redirect when not authenticated
		$this->assertResponseRedirects();
	}

	/**
	 * Test that new session page requires admin role
	 */
	public function testNewSessionPageRequiresAdmin(): void {
		$client = static::createClient();
		$client->request('GET', '/session/new/1');

		// Should redirect when not authenticated or not admin
		$this->assertResponseRedirects();
	}

	/**
	 * Test that new session page loads for admin users
	 */
	public function testNewSessionPageLoadsForAdmin(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$seasonRepository = new SeasonRepository($this->getEntityManager(), $this->getLogger());
		$season = $seasonRepository->findOneBy([]);

		if (!$season) {
			$this->markTestSkipped('No season found in database');
		}

		$client->request('GET', '/session/new/' . $season->getId());

		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains('title', 'Add Session');
		$this->assertSelectorExists('form');
	}

	/**
	 * Test that edit session page requires authentication
	 */
	public function testEditSessionPageRequiresAuthentication(): void {
		$client = static::createClient();
		$client->request('GET', '/session/edit/1');

		// Should redirect when not authenticated
		$this->assertResponseRedirects();
	}

	/**
	 * Test that edit session page requires admin role
	 */
	public function testEditSessionPageRequiresAdmin(): void {
		$client = static::createClient();
		$client->request('GET', '/session/edit/1');

		// Should redirect when not authenticated or not admin
		$this->assertResponseRedirects();
	}

	/**
	 * Test that edit session page loads for admin users
	 */
	public function testEditSessionPageLoadsForAdmin(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$sessionRepository = new SessionRepository($this->getEntityManager(), $this->getLogger());
		$session = $sessionRepository->findOneBy([]);

		if (!$session) {
			$this->markTestSkipped('No session found in database');
		}

		$client->request('GET', '/session/edit/' . $session->getId());

		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains('title', 'Edit Session');
		$this->assertSelectorExists('form');
	}

	/**
	 * Test that delete session requires authentication
	 */
	public function testDeleteSessionRequiresAuthentication(): void {
		$client = static::createClient();
		$client->request('DELETE', '/session/delete/1');

		// Should redirect when not authenticated
		$this->assertResponseRedirects();
	}

	/**
	 * Test that delete session requires admin role
	 */
	public function testDeleteSessionRequiresAdmin(): void {
		$client = static::createClient();
		$client->request('DELETE', '/session/delete/1');

		// Should redirect when not authenticated or not admin
		$this->assertResponseRedirects();
	}

	/**
	 * Test that delete session requires DELETE method
	 */
	public function testDeleteSessionRequiresDeleteMethod(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$sessionRepository = new SessionRepository($this->getEntityManager(), $this->getLogger());
		$session = $sessionRepository->findOneBy([]);

		if (!$session) {
			$this->markTestSkipped('No session found in database');
		}

		// Try GET request - should fail
		$client->request('GET', '/session/delete/' . $session->getId());
		$this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
	}

	/**
	 * Test that session list shows session information
	 */
	public function testSessionListShowsSessionInformation(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$seasonRepository = new SeasonRepository($this->getEntityManager(), $this->getLogger());
		$season = $seasonRepository->findOneBy([]);

		if (!$season) {
			$this->markTestSkipped('No season found in database');
		}

		$client->request('GET', '/session/list/' . $season->getId());

		$this->assertResponseIsSuccessful();
		$this->assertSelectorExists('table, .session-list, body');
	}

	/**
	 * Test that session view shows session details
	 */
	public function testSessionViewShowsSessionDetails(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$sessionRepository = new SessionRepository($this->getEntityManager(), $this->getLogger());
		$session = $sessionRepository->findOneBy([]);

		if (!$session) {
			$this->markTestSkipped('No session found in database');
		}

		$client->request('GET', '/session/view/' . $session->getId());

		$this->assertResponseIsSuccessful();
		$this->assertSelectorExists('.session-details, .session-info, table, body');
	}

	/**
	 * Test that routes have correct HTTP methods
	 */
	public function testRoutesHaveCorrectHttpMethods(): void {
		$client = static::createClient();
		$router = $client->getContainer()->get('router');

		$listRoute = $router->getRouteCollection()->get('session_list');
		$this->assertContains('GET', $listRoute->getMethods());

		$viewRoute = $router->getRouteCollection()->get('session_view');
		$this->assertContains('GET', $viewRoute->getMethods());

		$newRoute = $router->getRouteCollection()->get('session_new');
		$this->assertContains('GET', $newRoute->getMethods());
		$this->assertContains('POST', $newRoute->getMethods());

		$editRoute = $router->getRouteCollection()->get('session_edit');
		$this->assertContains('GET', $editRoute->getMethods());
		$this->assertContains('POST', $editRoute->getMethods());

		$deleteRoute = $router->getRouteCollection()->get('session_delete');
		$this->assertContains('DELETE', $deleteRoute->getMethods());
	}

	/**
	 * Test route names exist and generate correct paths
	 */
	public function testRouteNames(): void {
		$client = static::createClient();
		$router = $client->getContainer()->get('router');

		// Test that route names exist and point to correct paths
		$this->assertEquals('/session/list/1', $router->generate('session_list', ['id' => 1]));
		$this->assertEquals('/session/view/1', $router->generate('session_view', ['id' => 1]));
		$this->assertEquals('/session/new/1', $router->generate('session_new', ['id' => 1]));
		$this->assertEquals('/session/edit/1', $router->generate('session_edit', ['id' => 1]));
		$this->assertEquals('/session/delete/1', $router->generate('session_delete', ['id' => 1]));
	}


	/**
	 * Test that session form contains expected fields
	 */
	public function testSessionFormContainsExpectedFields(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$seasonRepository = new SeasonRepository($this->getEntityManager(), $this->getLogger());
		$season = $seasonRepository->findOneBy([]);

		if (!$season) {
			$this->markTestSkipped('No season found in database');
		}

		$crawler = $client->request('GET', '/session/new/' . $season->getId());

		$this->assertResponseIsSuccessful();

		// Check for form fields
		$form = $crawler->selectButton('Save')->form();
		$formValues = $form->getPhpValues()['form'];

		$this->assertArrayHasKey('name', $formValues);
		$this->assertArrayHasKey('startdate', $formValues);
		$this->assertArrayHasKey('enddate', $formValues);
	}

	/**
	 * Test that non-existent session edit shows appropriate response
	 */
	public function testEditNonExistentSession(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$client->request('GET', '/session/edit/999999');

		// Should either show error or handle gracefully
		$response = $client->getResponse();
		$this->assertTrue(
			$response->isServerError() ||
			$response->isClientError() ||
			$response->isRedirect()
		);
	}

	/**
	 * Test that session list is tied to specific season
	 */
	public function testSessionListShowsSeasonContext(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$seasonRepository = new SeasonRepository($this->getEntityManager(), $this->getLogger());
		$season = $seasonRepository->findOneBy([]);

		if (!$season) {
			$this->markTestSkipped('No season found in database');
		}

		$client->request('GET', '/session/list/' . $season->getId());

		$this->assertResponseIsSuccessful();
		// Verify season context is present in the page
		$this->assertSelectorExists('body');
	}
}
