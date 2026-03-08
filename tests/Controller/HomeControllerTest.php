<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase {
    public function testAccessDeniedRoute(): void {
        $client = static::createClient();
        $client->request('GET', '/accessdenied');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('html', 'Security Violation');
    }

    public function testHomeRoute(): void {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('html', 'League Tracker Home');
    }

    public function testPaymentRoute(): void {
        $client = static::createClient();
        $client->request('GET', '/payment');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('html', 'League Payment');
    }

    public function testHelpRoute(): void {
        $client = static::createClient();
        $client->request('GET', '/help');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('html', 'League Tracker Help');
    }

    public function testRouteNames(): void {
        $client = static::createClient();
        $router = $client->getContainer()->get('router');

        // Test that route names exist and point to correct paths
        $this->assertEquals('/accessdenied', $router->generate('accessdenied'));
        $this->assertEquals('/', $router->generate('home'));
        $this->assertEquals('/payment', $router->generate('payment'));
        $this->assertEquals('/help', $router->generate('help'));
    }

    public function testOnlyGetMethodsAllowed(): void {
        $client = static::createClient();

        // Test that POST requests are not allowed
        $client->request('POST', '/');
        $this->assertResponseStatusCodeSame(405); // Method Not Allowed

        $client->request('POST', '/help');
        $this->assertResponseStatusCodeSame(405);
    }
}
