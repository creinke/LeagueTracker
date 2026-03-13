<?php

namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiSecurityControllerTest extends WebTestCase {
    public function testLoginWithValidCredentials(): void {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'member',
                'password' => 'password' // Assuming this is the test password
            ])
        );

        $this->assertResponseIsSuccessful();
        $response = $client->getResponse();
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('apiToken', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('league', $data);
        $this->assertEquals('member', $data['user']['username']);
    }

    public function testLoginWithInvalidCredentials(): void {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'member',
                'password' => 'wrong-password'
            ])
        );

        $this->assertResponseStatusCodeSame(401);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Invalid credentials', $data['error']);
    }
}
