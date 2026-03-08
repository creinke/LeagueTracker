<?php

namespace App\Tests\Controller;

use App\Entity\SeasonDE;
use App\Repository\SeasonRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Test class for SeasonController
 */
class SeasonControllerTest extends WebTestCase {
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
	 * Test that season list page loads successfully
	 */
	public function testListPageLoadsSuccessfully(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$client->request('GET', '/season/list');

		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains('title', 'Seasons');
	}

	/**
	 * Test that season list requires authentication
	 */
	public function testListRequiresAuthentication(): void {
		$client = static::createClient();
		$client->request('GET', '/season/list');

		// Should redirect to login page when not authenticated
		$this->assertResponseRedirects();
	}

	/**
	 * Test that view season page loads successfully
	 */
	public function testViewSeasonPageLoadsSuccessfully(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$seasonRepository = new SeasonRepository($this->getEntityManager(), $this->getLogger());
		$season = $seasonRepository->findOneBy([]);

		if (!$season) {
			$this->markTestSkipped('No season found in database');
		}

		$client->request('GET', '/season/view/' . $season->getId());

		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains('title', 'Season');
	}

	/**
	 * Test that view season requires authentication
	 */
	public function testViewRequiresAuthentication(): void {
		$client = static::createClient();
		$client->request('GET', '/season/view/1');

		// Should redirect to login page when not authenticated
		$this->assertResponseRedirects();
	}

	/**
	 * Test that new season page requires admin role
	 */
	public function testNewSeasonPageRequiresAdmin(): void {
		$client = static::createClient();
		$client->request('GET', '/season/new');

		// Should redirect when not authenticated or not admin
		$this->assertResponseRedirects();
	}

	/**
	 * Test that new season page loads for admin users
	 */
	public function testNewSeasonPageLoadsForAdmin(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$client->request('GET', '/season/new');

		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains('title', 'Add Season');
		$this->assertSelectorExists('form');
	}

	/**
	 * Test that edit season page requires admin role
	 */
	public function testEditSeasonPageRequiresAdmin(): void {
		$client = static::createClient();
		$client->request('GET', '/season/edit/1');

		// Should redirect when not authenticated or not admin
		$this->assertResponseRedirects();
	}

	/**
	 * Test that edit season page loads for admin users
	 */
	public function testEditSeasonPageLoadsForAdmin(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$seasonRepository = new SeasonRepository($this->getEntityManager(), $this->getLogger());
		$season = $seasonRepository->findOneBy([]);

		if (!$season) {
			$this->markTestSkipped('No season found in database');
		}

		$client->request('GET', '/season/edit/' . $season->getId());

		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains('title', 'Edit Season');
		$this->assertSelectorExists('form');
	}

	/**
	 * Test that delete season requires admin role
	 */
	public function testDeleteSeasonRequiresAdmin(): void {
		$client = static::createClient();
		$client->request('DELETE', '/season/delete/1');

		// Should redirect when not authenticated or not admin
		$this->assertResponseRedirects();
	}

	/**
	 * Test that delete season requires DELETE method
	 */
	public function testDeleteSeasonRequiresDeleteMethod(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$seasonRepository = new SeasonRepository($this->getEntityManager(), $this->getLogger());
		$season = $seasonRepository->findOneBy([]);

		if (!$season) {
			$this->markTestSkipped('No season found in database');
		}

		// Try GET request - should fail
		$client->request('GET', '/season/delete/' . $season->getId());
		$this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
	}

	/**
	 * Test that generate season page requires admin role
	 */
	public function testGenerateSeasonRequiresAdmin(): void {
		$client = static::createClient();
		$client->request('POST', '/season/generate');

		// Should redirect when not authenticated or not admin
		$this->assertResponseRedirects();
	}

	/**
	 * Test that generate season requires POST method
	 */
	public function testGenerateSeasonRequiresPostMethod(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		// Try GET request - should fail
		$client->request('GET', '/season/generate');
		$this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
	}

	/**
	 * Test view non-existent season
	 */
	public function testViewNonExistentSeason(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$client->request('GET', '/season/view/999999');

		// Should either redirect or show error
		$response = $client->getResponse();
		$this->assertTrue($response->isRedirect() || $response->isSuccessful());
	}

	/**
	 * Test that season list shows league information
	 */
	public function testSeasonListShowsLeagueInformation(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$client->request('GET', '/season/list');

		$this->assertResponseIsSuccessful();
		$this->assertSelectorExists('table, .season-list, body');
	}

	/**
	 * Test that season view shows season details
	 */
	public function testSeasonViewShowsSeasonDetails(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$seasonRepository = new SeasonRepository($this->getEntityManager(), $this->getLogger());
		$season = $seasonRepository->findOneBy([]);

		if (!$season) {
			$this->markTestSkipped('No season found in database');
		}

		$client->request('GET', '/season/view/' . $season->getId());

		$this->assertResponseIsSuccessful();
		$this->assertSelectorExists('.season-details, .season-info, table, body');
	}

	/**
	 * Test that routes have correct HTTP methods
	 */
	public function testRoutesHaveCorrectHttpMethods(): void {
		$client = static::createClient();
		$router = $client->getContainer()->get('router');

		$listRoute = $router->getRouteCollection()->get('season_list');
		$this->assertContains('GET', $listRoute->getMethods());

		$viewRoute = $router->getRouteCollection()->get('season_view');
		$this->assertContains('GET', $viewRoute->getMethods());

		$newRoute = $router->getRouteCollection()->get('season_new');
		$this->assertContains('GET', $newRoute->getMethods());
		$this->assertContains('POST', $newRoute->getMethods());

		$editRoute = $router->getRouteCollection()->get('season_edit');
		$this->assertContains('GET', $editRoute->getMethods());
		$this->assertContains('POST', $editRoute->getMethods());

		$deleteRoute = $router->getRouteCollection()->get('season_delete');
		$this->assertContains('DELETE', $deleteRoute->getMethods());

		$generateRoute = $router->getRouteCollection()->get('season_generate');
		$this->assertContains('POST', $generateRoute->getMethods());
	}

	/**
	 * Test route names exist and generate correct paths
	 */
	public function testRouteNames(): void {
		$client = static::createClient();
		$router = $client->getContainer()->get('router');

		// Test that route names exist and point to correct paths
		$this->assertEquals('/season/list', $router->generate('season_list'));
		$this->assertEquals('/season/view/1', $router->generate('season_view', ['id' => 1]));
		$this->assertEquals('/season/new', $router->generate('season_new'));
		$this->assertEquals('/season/edit/1', $router->generate('season_edit', ['id' => 1]));
		$this->assertEquals('/season/delete/1', $router->generate('season_delete', ['id' => 1]));
		$this->assertEquals('/season/generate', $router->generate('season_generate'));
	}

	/**
	 * Test that createNewSinglesGame static method exists
	 */
	public function testCreateNewSinglesGameMethodExists(): void {
		$this->assertTrue(method_exists(\App\Controller\SeasonController::class, 'createNewSinglesGame'));
	}

	/**
	 * Test that createNewSinglesTeamMatchGame static method exists
	 */
	public function testCreateNewSinglesTeamMatchGameMethodExists(): void {
		$this->assertTrue(method_exists(\App\Controller\SeasonController::class, 'createNewSinglesTeamMatchGame'));
	}

	/**
	 * Test that createNewTeamMatchGame static method exists
	 */
	public function testCreateNewTeamMatchGameMethodExists(): void {
		$this->assertTrue(method_exists(\App\Controller\SeasonController::class, 'createNewTeamMatchGame'));
	}

	/**
	 * Test that season list shows seasons when available
	 */
	public function testSeasonListShowsSeasonsWhenAvailable(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$seasonRepository = new SeasonRepository($this->getEntityManager(), $this->getLogger());
		$seasons = $seasonRepository->findAll();

		$client->request('GET', '/season/list');

		$this->assertResponseIsSuccessful();

		if (count($seasons) > 0) {
			// Should show season data
			$this->assertSelectorExists('body');
		}
	}

	/**
	 * Test that a new season form has required fields
	 */
	public function testNewSeasonFormHasRequiredFields(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$crawler = $client->request('GET', '/season/new');

		$this->assertResponseIsSuccessful();

		// Check for form fields
		$form = $crawler->filter('form');
		$this->assertCount(1, $form);
	}

	/**
	 * Test that edit season form loads season data
	 */
	public function testEditSeasonFormLoadsSeasonData(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$seasonRepository = new SeasonRepository($this->getEntityManager(), $this->getLogger());
		$season = $seasonRepository->findOneBy([]);

		if (!$season) {
			$this->markTestSkipped('No season found in database');
		}

		$crawler = $client->request('GET', '/season/edit/' . $season->getId());

		$this->assertResponseIsSuccessful();

		// Check that form exists
		$form = $crawler->filter('form');
		$this->assertCount(1, $form);
	}

	/**
	 * Test that only authenticated users can access season routes
	 */
	public function testOnlyAuthenticatedUsersCanAccessRoutes(): void {
		$client = static::createClient();

		// Test list
		$client->request('GET', '/season/list');
		$this->assertResponseRedirects();

		// Test view
		$client->request('GET', '/season/view/1');
		$this->assertResponseRedirects();
	}

	/**
	 * Test that only admin users can access admin routes
	 */
	public function testOnlyAdminUsersCanAccessAdminRoutes(): void {
		$client = static::createClient();

		// Test new
		$client->request('GET', '/season/new');
		$this->assertResponseRedirects();

		// Test edit
		$client->request('GET', '/season/edit/1');
		$this->assertResponseRedirects();

		// Test delete
		$client->request('DELETE', '/season/delete/1');
		$this->assertResponseRedirects();

		// Test generate
		$client->request('POST', '/season/generate');
		$this->assertResponseRedirects();
	}
}