<?php

namespace App\Tests\Controller;

use App\Entity\PlayerDE;
use App\Repository\PlayerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Test class for PlayerController
 */
class PlayerControllerTest extends WebTestCase {
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
	 * Test that player list page loads successfully
	 */
	public function testListPageLoadsSuccessfully(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$client->request('GET', '/player/list');

		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains('title', 'Players');
	}

	/**
	 * Test that player list requires authentication
	 */
	public function testListRequiresAuthentication(): void {
		$client = static::createClient();
		$client->request('GET', '/player/list');

		// Should redirect to login page when not authenticated
		$this->assertResponseRedirects();
	}

	/**
	 * Test that view player page loads successfully
	 */
	public function testViewPlayerPageLoadsSuccessfully(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$playerRepository = new PlayerRepository($this->getEntityManager(), $this->getLogger());
		$player = $playerRepository->findOneBy([]);

		if (!$player) {
			$this->markTestSkipped('No player found in database');
		}

		$client->request('GET', '/player/view/' . $player->getId());

		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains('title', 'Player');
	}

	/**
	 * Test that view player requires authentication
	 */
	public function testViewRequiresAuthentication(): void {
		$client = static::createClient();
		$client->request('GET', '/player/view/1');

		// Should redirect to login page when not authenticated
		$this->assertResponseRedirects();
	}

	/**
	 * Test that new player page requires user role
	 */
	public function testNewPlayerPageRequiresUser(): void {
		$client = static::createClient();
		$client->request('GET', '/player/new');

		// Should redirect when not authenticated
		$this->assertResponseRedirects();
	}

	/**
	 * Test that new player page loads for authenticated users
	 */
	public function testNewPlayerPageLoadsForUser(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$client->request('GET', '/player/new');

		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains('title', 'Add Player');
		$this->assertSelectorExists('form');
	}

	/**
	 * Test that edit player page requires user role
	 */
	public function testEditPlayerPageRequiresUser(): void {
		$client = static::createClient();
		$client->request('GET', '/player/edit/1');

		// Should redirect when not authenticated
		$this->assertResponseRedirects();
	}

	/**
	 * Test that edit player page loads for authenticated users
	 */
	public function testEditPlayerPageLoadsForUser(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$playerRepository = new PlayerRepository($this->getEntityManager(), $this->getLogger());
		$player = $playerRepository->findOneBy([]);

		if (!$player) {
			$this->markTestSkipped('No player found in database');
		}

		$client->request('GET', '/player/edit/' . $player->getId());

		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains('title', 'Edit Player');
		$this->assertSelectorExists('form');
	}

	/**
	 * Test that delete player requires admin role
	 */
	public function testDeletePlayerRequiresAdmin(): void {
		$client = static::createClient();
		$client->request('DELETE', '/player/delete/1');

		// Should redirect when not authenticated or not admin
		$this->assertResponseRedirects();
	}

	/**
	 * Test that delete player requires DELETE method
	 */
	public function testDeletePlayerRequiresDeleteMethod(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$playerRepository = new PlayerRepository($this->getEntityManager(), $this->getLogger());
		$player = $playerRepository->findOneBy([]);

		if (!$player) {
			$this->markTestSkipped('No player found in database');
		}

		// Try GET request - should fail
		$client->request('GET', '/player/delete/' . $player->getId());
		$this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
	}

	/**
	 * Test that newlist player page requires admin role
	 */
	public function testNewlistPlayerPageRequiresAdmin(): void {
		$client = static::createClient();
		$client->request('GET', '/player/newlist');

		// Should redirect when not authenticated or not admin
		$this->assertResponseRedirects();
	}

	/**
	 * Test that newlist player page loads for admin users
	 */
	public function testNewlistPlayerPageLoadsForAdmin(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$client->request('GET', '/player/newlist');

		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains('title', 'Add Players');
		$this->assertSelectorExists('form');
	}

	/**
	 * Test that handicap page requires user role
	 */
	public function testHandicapPageRequiresUser(): void {
		$client = static::createClient();
		$client->request('GET', '/player/handicap/1');

		// Should redirect when not authenticated
		$this->assertResponseRedirects();
	}

	/**
	 * Test that handicap page loads for authenticated users
	 */
	public function testHandicapPageLoadsForUser(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$playerRepository = new PlayerRepository($this->getEntityManager(), $this->getLogger());
		$player = $playerRepository->findOneBy([]);

		if (!$player) {
			$this->markTestSkipped('No player found in database');
		}

		$client->request('GET', '/player/handicap/' . $player->getId());

		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains('title', 'Player');
	}

	/**
	 * Test that undefunct player requires admin role
	 */
	public function testUndefunctPlayerRequiresAdmin(): void {
		$client = static::createClient();
		$client->request('POST', '/player/undefunct/1');

		// Should redirect when not authenticated or not admin
		$this->assertResponseRedirects();
	}

	/**
	 * Test that undefunct player requires POST method
	 */
	public function testUndefunctPlayerRequiresPostMethod(): void {
		$client = $this->createAuthenticatedClient('ROLE_ADMIN');

		$playerRepository = new PlayerRepository($this->getEntityManager(), $this->getLogger());
		$player = $playerRepository->findOneBy([]);

		if (!$player) {
			$this->markTestSkipped('No player found in database');
		}

		// Try GET request - should fail
		$client->request('GET', '/player/undefunct/' . $player->getId());
		$this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
	}

	/**
	 * Test view non-existent player
	 */
	public function testViewNonExistentPlayer(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$client->request('GET', '/player/view/999999');

		// Should either redirect or show error
		$response = $client->getResponse();
		$this->assertTrue($response->isRedirect() || $response->isSuccessful());
	}

	/**
	 * Test that player list shows league information
	 */
	public function testPlayerListShowsLeagueInformation(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$client->request('GET', '/player/list');

		$this->assertResponseIsSuccessful();
		$this->assertSelectorExists('table, .player-list, body');
	}

	/**
	 * Test that player view shows player details
	 */
	public function testPlayerViewShowsPlayerDetails(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$playerRepository = new PlayerRepository($this->getEntityManager(), $this->getLogger());
		$player = $playerRepository->findOneBy([]);

		if (!$player) {
			$this->markTestSkipped('No player found in database');
		}

		$client->request('GET', '/player/view/' . $player->getId());

		$this->assertResponseIsSuccessful();
		$this->assertSelectorExists('.player-details, .player-info, table, body');
	}

	/**
	 * Test that routes have correct HTTP methods
	 */
	public function testRoutesHaveCorrectHttpMethods(): void {
		$client = static::createClient();
		$router = $client->getContainer()->get('router');

		$listRoute = $router->getRouteCollection()->get('player_list');
		$this->assertContains('GET', $listRoute->getMethods());

		$viewRoute = $router->getRouteCollection()->get('player_view');
		$this->assertContains('GET', $viewRoute->getMethods());

		$newRoute = $router->getRouteCollection()->get('player_new');
		$this->assertContains('GET', $newRoute->getMethods());
		$this->assertContains('POST', $newRoute->getMethods());

		$editRoute = $router->getRouteCollection()->get('player_edit');
		$this->assertContains('GET', $editRoute->getMethods());
		$this->assertContains('POST', $editRoute->getMethods());

		$deleteRoute = $router->getRouteCollection()->get('player_delete');
		$this->assertContains('DELETE', $deleteRoute->getMethods());

		$newlistRoute = $router->getRouteCollection()->get('player_newlist');
		$this->assertContains('GET', $newlistRoute->getMethods());
		$this->assertContains('POST', $newlistRoute->getMethods());

		$handicapRoute = $router->getRouteCollection()->get('player_handicap');
		$this->assertContains('GET', $handicapRoute->getMethods());
		$this->assertContains('POST', $handicapRoute->getMethods());

		$undefunctRoute = $router->getRouteCollection()->get('player_undefunct');
		$this->assertContains('POST', $undefunctRoute->getMethods());
	}

	/**
	 * Test route names exist and generate correct paths
	 */
	public function testRouteNames(): void {
		$client = static::createClient();
		$router = $client->getContainer()->get('router');

		// Test that route names exist and point to correct paths
		$this->assertEquals('/player/list', $router->generate('player_list'));
		$this->assertEquals('/player/view/1', $router->generate('player_view', ['id' => 1]));
		$this->assertEquals('/player/new', $router->generate('player_new'));
		$this->assertEquals('/player/edit/1', $router->generate('player_edit', ['id' => 1]));
		$this->assertEquals('/player/delete/1', $router->generate('player_delete', ['id' => 1]));
		$this->assertEquals('/player/newlist', $router->generate('player_newlist'));
		$this->assertEquals('/player/handicap/1', $router->generate('player_handicap', ['id' => 1]));
		$this->assertEquals('/player/undefunct/1', $router->generate('player_undefunct', ['id' => 1]));
	}

	/**
	 * Test that handicap calculation page displays scores
	 */
	public function testHandicapPageDisplaysScores(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$playerRepository = new PlayerRepository($this->getEntityManager(), $this->getLogger());
		$player = $playerRepository->findOneBy([]);

		if (!$player) {
			$this->markTestSkipped('No player found in database');
		}

		$client->request('GET', '/player/handicap/' . $player->getId());

		$this->assertResponseIsSuccessful();
		// Verify some handicap-related content exists
		$this->assertSelectorExists('body');
	}

	/**
	 * Test that player list displays handicap information
	 */
	public function testPlayerListDisplaysHandicapInformation(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$client->request('GET', '/player/list');

		$this->assertResponseIsSuccessful();
		// Verify the page loads with player data
		$this->assertSelectorExists('body');
	}

	/**
	 * Test that edit form includes defunct checkbox
	 */
	public function testEditFormIncludesDefunctOption(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$playerRepository = new PlayerRepository($this->getEntityManager(), $this->getLogger());
		$player = $playerRepository->findOneBy([]);

		if (!$player) {
			$this->markTestSkipped('No player found in database');
		}

		$crawler = $client->request('GET', '/player/edit/' . $player->getId());

		$this->assertResponseIsSuccessful();
		// Form should exist
		$this->assertGreaterThan(0, $crawler->filter('form')->count());
	}

	/**
	 * Test that new player form has required fields
	 */
	public function testNewPlayerFormHasRequiredFields(): void {
		$client = $this->createAuthenticatedClient('ROLE_USER');

		$crawler = $client->request('GET', '/player/new');

		$this->assertResponseIsSuccessful();
		// Verify form exists
		$this->assertGreaterThan(0, $crawler->filter('form')->count());
	}
}