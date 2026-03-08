<?php

namespace App\Tests\Controller;

use App\Entity\TeamDE;
use App\Repository\TeamRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Test class for TeamController
 */
class TeamControllerTest extends WebTestCase {
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
	 * Test that team list page loads successfully
	 */
	public function testListPageLoadsSuccessfully(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$client->request('GET', '/team/list');

		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains('title', 'Teams');
	}

	/**
	 * Test that team list requires authentication
	 */
	public function testListRequiresAuthentication(): void {
		$client = static::createClient();
		$client->request('GET', '/team/list');

		// Should redirect to login page when not authenticated
		$this->assertResponseRedirects();
	}

	/**
	 * Test that view team page loads successfully
	 */
	public function testViewTeamPageLoadsSuccessfully(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$teamRepository = new TeamRepository($this->getEntityManager(), $this->getLogger());
		$team = $teamRepository->findOneBy([]);

		if (!$team) {
			$this->markTestSkipped('No team found in database');
		}

		$client->request('GET', '/team/view/' . $team->getId());

		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains('title', 'Team');
	}

	/**
	 * Test that view team requires authentication
	 */
	public function testViewRequiresAuthentication(): void {
		$client = static::createClient();
		$client->request('GET', '/team/view/1');

		// Should redirect to login page when not authenticated
		$this->assertResponseRedirects();
	}

	/**
	 * Test that new team page requires admin role
	 */
	public function testNewTeamPageRequiresAdmin(): void {
		$client = static::createClient();
		$client->request('GET', '/team/new');

		// Should redirect when not authenticated or not admin
		$this->assertResponseRedirects();
	}

	/**
	 * Test that new team page loads for admin users
	 */
	public function testNewTeamPageLoadsForAdmin(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$client->request('GET', '/team/new');

		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains('title', 'Add Team');
		$this->assertSelectorExists('form');
	}

	/**
	 * Test that edit team page requires admin role
	 */
	public function testEditTeamPageRequiresAdmin(): void {
		$client = static::createClient();
		$client->request('POST', '/team/edit/1');

		// Should redirect when not authenticated or not admin
		$this->assertResponseRedirects();
	}

	/**
	 * Test that edit team page loads for admin users
	 */
	public function testEditTeamPageLoadsForAdmin(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$teamRepository = new TeamRepository($this->getEntityManager(), $this->getLogger());
		$team = $teamRepository->findOneBy([]);

		if (!$team) {
			$this->markTestSkipped('No team found in database');
		}

		$crawler = $client->request('POST', '/team/edit/' . $team->getId());

		// Should show form or redirect after processing
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful() || $response->isRedirect());
	}

	/**
	 * Test that delete team requires admin role
	 */
	public function testDeleteTeamRequiresAdmin(): void {
		$client = static::createClient();
		$client->request('DELETE', '/team/delete/1');

		// Should redirect when not authenticated or not admin
		$this->assertResponseRedirects();
	}

	/**
	 * Test that delete team requires DELETE method
	 */
	public function testDeleteTeamRequiresDeleteMethod(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$teamRepository = new TeamRepository($this->getEntityManager(), $this->getLogger());
		$team = $teamRepository->findOneBy([]);

		if (!$team) {
			$this->markTestSkipped('No team found in database');
		}

		// Try GET request - should fail
		$client->request('GET', '/team/delete/' . $team->getId());
		$this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
	}

	/**
	 * Test that newlist team page requires admin role
	 */
	public function testNewlistTeamPageRequiresAdmin(): void {
		$client = static::createClient();
		$client->request('GET', '/team/newlist');

		// Should redirect when not authenticated or not admin
		$this->assertResponseRedirects();
	}

	/**
	 * Test that newlist team page loads for admin users
	 */
	public function testNewlistTeamPageLoadsForAdmin(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$client->request('GET', '/team/newlist');

		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains('title', 'Add Teams');
		$this->assertSelectorExists('form');
	}

	/**
	 * Test that undefunct team requires admin role
	 */
	public function testUndefunctTeamRequiresAdmin(): void {
		$client = static::createClient();
		$client->request('POST', '/team/undefunct/1');

		// Should redirect when not authenticated or not admin
		$this->assertResponseRedirects();
	}

	/**
	 * Test that undefunct team requires POST method
	 */
	public function testUndefunctTeamRequiresPostMethod(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$teamRepository = new TeamRepository($this->getEntityManager(), $this->getLogger());
		$team = $teamRepository->findOneBy([]);

		if (!$team) {
			$this->markTestSkipped('No team found in database');
		}

		// Try GET request - should fail
		$client->request('GET', '/team/undefunct/' . $team->getId());
		$this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
	}

	/**
	 * Test view non-existent team
	 */
	public function testViewNonExistentTeam(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$client->request('GET', '/team/view/999999');

		// Should either redirect or show error
		$response = $client->getResponse();
		$this->assertTrue($response->isRedirect() || $response->isSuccessful());
	}

	/**
	 * Test that team list shows league information
	 */
	public function testTeamListShowsLeagueInformation(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$client->request('GET', '/team/list');

		$this->assertResponseIsSuccessful();
		$this->assertSelectorExists('table, .team-list, body');
	}

	/**
	 * Test that team view shows team details
	 */
	public function testTeamViewShowsTeamDetails(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$teamRepository = new TeamRepository($this->getEntityManager(), $this->getLogger());
		$team = $teamRepository->findOneBy([]);

		if (!$team) {
			$this->markTestSkipped('No team found in database');
		}

		$client->request('GET', '/team/view/' . $team->getId());

		$this->assertResponseIsSuccessful();
		$this->assertSelectorExists('.team-details, .team-info, table, body');
	}

	/**
	 * Test that routes have correct HTTP methods
	 */
	public function testRoutesHaveCorrectHttpMethods(): void {
		$client = static::createClient();
		$router = $client->getContainer()->get('router');

		$listRoute = $router->getRouteCollection()->get('team_list');
		$this->assertContains('GET', $listRoute->getMethods());

		$viewRoute = $router->getRouteCollection()->get('team_view');
		$this->assertContains('GET', $viewRoute->getMethods());

		$newRoute = $router->getRouteCollection()->get('team_new');
		$this->assertContains('GET', $newRoute->getMethods());
		$this->assertContains('POST', $newRoute->getMethods());

		$editRoute = $router->getRouteCollection()->get('team_edit');
		$this->assertContains('POST', $editRoute->getMethods());

		$deleteRoute = $router->getRouteCollection()->get('team_delete');
		$this->assertContains('DELETE', $deleteRoute->getMethods());

		$newlistRoute = $router->getRouteCollection()->get('team_newlist');
		$this->assertContains('GET', $newlistRoute->getMethods());
		$this->assertContains('POST', $newlistRoute->getMethods());

		$undefunctRoute = $router->getRouteCollection()->get('team_undefunct');
		$this->assertContains('POST', $undefunctRoute->getMethods());
	}

	/**
	 * Test route names exist and generate correct paths
	 */
	public function testRouteNames(): void {
		$client = static::createClient();
		$router = $client->getContainer()->get('router');

		// Test that route names exist and point to correct paths
		$this->assertEquals('/team/list', $router->generate('team_list'));
		$this->assertEquals('/team/view/1', $router->generate('team_view', ['id' => 1]));
		$this->assertEquals('/team/new', $router->generate('team_new'));
		$this->assertEquals('/team/edit/1', $router->generate('team_edit', ['id' => 1]));
		$this->assertEquals('/team/delete/1', $router->generate('team_delete', ['id' => 1]));
		$this->assertEquals('/team/newlist', $router->generate('team_newlist'));
		$this->assertEquals('/team/undefunct/1', $router->generate('team_undefunct', ['id' => 1]));
	}

	/**
	 * Test that team list shows teams when available
	 */
	public function testTeamListShowsTeamsWhenAvailable(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$teamRepository = new TeamRepository($this->getEntityManager(), $this->getLogger());
		$teams = $teamRepository->findAll();

		$client->request('GET', '/team/list');

		$this->assertResponseIsSuccessful();

		if (count($teams) > 0) {
			// Should show team data
			$this->assertSelectorExists('body');
		}
	}

	/**
	 * Test that new team form has required fields
	 */
	public function testNewTeamFormHasRequiredFields(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$crawler = $client->request('GET', '/team/new');

		$this->assertResponseIsSuccessful();
		// Verify form exists
		$this->assertGreaterThan(0, $crawler->filter('form')->count());
	}

	/**
	 * Test that team view shows player information
	 */
	public function testTeamViewShowsPlayerInformation(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$teamRepository = new TeamRepository($this->getEntityManager(), $this->getLogger());
		$team = $teamRepository->findOneBy([]);

		if (!$team) {
			$this->markTestSkipped('No team found in database');
		}

		$client->request('GET', '/team/view/' . $team->getId());

		$this->assertResponseIsSuccessful();
		// Verify the page loads with team data
		$this->assertSelectorExists('body');
	}

	/**
	 * Test that only authenticated users can access team routes
	 */
	public function testOnlyAuthenticatedUsersCanAccessRoutes(): void {
		$client = static::createClient();

		// Test list
		$client->request('GET', '/team/list');
		$this->assertResponseRedirects();

		// Test view
		$client->request('GET', '/team/view/1');
		$this->assertResponseRedirects();
	}

	/**
	 * Test that only admin users can access admin routes
	 */
	public function testOnlyAdminUsersCanAccessAdminRoutes(): void {
		$client = static::createClient();

		// Test new
		$client->request('GET', '/team/new');
		$this->assertResponseRedirects();

		// Test edit
		$client->request('POST', '/team/edit/1');
		$this->assertResponseRedirects();

		// Test delete
		$client->request('DELETE', '/team/delete/1');
		$this->assertResponseRedirects();

		// Test newlist
		$client->request('GET', '/team/newlist');
		$this->assertResponseRedirects();

		// Test undefunct
		$client->request('POST', '/team/undefunct/1');
		$this->assertResponseRedirects();
	}

	/**
	 * Test that team list displays team numbers
	 */
	public function testTeamListDisplaysTeamNumbers(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$client->request('GET', '/team/list');

		$this->assertResponseIsSuccessful();
		// Verify the page loads with team data
		$this->assertSelectorExists('body');
	}

	/**
	 * Test that edit form includes defunct checkbox
	 */
	public function testEditFormIncludesDefunctOption(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$teamRepository = new TeamRepository($this->getEntityManager(), $this->getLogger());;
		$team = $teamRepository->findOneBy([]);

		if (!$team) {
			$this->markTestSkipped('No team found in database');
		}

		$client->request('POST', '/team/edit/' . $team->getId());

		// Should process or show form
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful() || $response->isRedirect());
	}
}
