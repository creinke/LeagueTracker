<?php

namespace App\Tests\Controller\Api;

use App\Entity\UserDE;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class ApiTestCase extends WebTestCase {
    protected ?EntityManagerInterface $entityManager = null;
    protected ?string $apiToken = null;

    protected function setUp(): void {
        parent::setUp();
    }

    protected function tearDown(): void {
        if ($this->entityManager) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
        parent::tearDown();
    }

    protected function createAuthenticatedClient(string $username = 'member'): KernelBrowser {
        $client = static::createClient();
        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $logger = $container->get(LoggerInterface::class);
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $userRepository = $this->entityManager->getRepository(\App\Entity\UserDE::class);
        $user = $userRepository->findOneBy(['username' => $username]);

        if ( ! $user) {
            throw new \RuntimeException("Test user {$username} not found.");
        }

        // Generate a token for the test
        $this->apiToken = bin2hex(random_bytes(32));
        $user->setApiToken($this->apiToken);
        $userRepository->saveUser($user);

        return $client;
    }

    protected function request(KernelBrowser $client, string $method, string $uri, array $parameters = [], array $files = [], array $server = [], string $content = null, bool $changeHistory = true): void {
        $server['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->apiToken;
        $server['CONTENT_TYPE'] = 'application/json';

        $client->request($method, $uri, $parameters, $files, $server, $content, $changeHistory);
    }

    protected function getJsonResponse(KernelBrowser $client): array {
        $response = $client->getResponse();
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data, 'Response content is not a valid JSON array');

        return $data;
    }
}
