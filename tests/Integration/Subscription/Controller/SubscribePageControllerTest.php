<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Subscription\Controller;

use PhpList\RestBundle\Subscription\Controller\SubscribePageController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorFixture;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorTokenFixture;
use PhpList\RestBundle\Tests\Integration\Subscription\Fixtures\SubscribePageFixture;

class SubscribePageControllerTest extends AbstractTestController
{
    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(
            SubscribePageController::class,
            self::getContainer()->get(SubscribePageController::class)
        );
    }

    public function testGetSubscribePageWithoutSessionReturnsForbidden(): void
    {
        $this->loadFixtures([AdministratorFixture::class, SubscribePageFixture::class]);

        self::getClient()->request('GET', '/api/v2/subscribe-pages/1');
        $this->assertHttpForbidden();
    }

    public function testGetSubscribePageWithSessionReturnsPage(): void
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            AdministratorTokenFixture::class,
            SubscribePageFixture::class,
        ]);

        $this->authenticatedJsonRequest('GET', '/api/v2/subscribe-pages/1');

        $this->assertHttpOkay();
        $data = $this->getDecodedJsonResponseContent();

        self::assertSame(1, $data['id']);
        self::assertSame('Welcome Page', $data['title']);
        self::assertTrue($data['active']);
        self::assertIsArray($data['owner']);
        self::assertSame(1, $data['owner']['id']);
        self::assertArrayHasKey('login_name', $data['owner']);
        self::assertArrayHasKey('email', $data['owner']);
        self::assertArrayHasKey('privileges', $data['owner']);
    }

    public function testGetSubscribePageWithSessionNotFound(): void
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            AdministratorTokenFixture::class,
            SubscribePageFixture::class,
        ]);

        $this->authenticatedJsonRequest('GET', '/api/v2/subscribe-pages/9999');

        $this->assertHttpNotFound();
    }

    public function testCreateSubscribePageWithoutSessionReturnsForbidden(): void
    {
        // no auth fixtures loaded here
        $payload = json_encode([
            'title' => 'new-page@example.org',
            'active' => true,
        ], JSON_THROW_ON_ERROR);

        $this->jsonRequest('POST', '/api/v2/subscribe-pages', content: $payload);

        $this->assertHttpForbidden();
    }

    public function testCreateSubscribePageWithSessionCreatesPage(): void
    {
        $payload = json_encode([
            'title' => 'new-page@example.org',
            'active' => true,
        ], JSON_THROW_ON_ERROR);

        $this->authenticatedJsonRequest('POST', '/api/v2/subscribe-pages', content: $payload);

        $this->assertHttpCreated();
        $data = $this->getDecodedJsonResponseContent();

        self::assertArrayHasKey('id', $data);
        self::assertIsInt($data['id']);
        self::assertGreaterThanOrEqual(1, $data['id']);
        self::assertSame('new-page@example.org', $data['title']);
        self::assertTrue($data['active']);
        self::assertIsArray($data['owner']);
        self::assertArrayHasKey('id', $data['owner']);
        self::assertArrayHasKey('login_name', $data['owner']);
        self::assertArrayHasKey('email', $data['owner']);
        self::assertArrayHasKey('privileges', $data['owner']);
    }

    public function testUpdateSubscribePageWithoutSessionReturnsForbidden(): void
    {
        $this->loadFixtures([AdministratorFixture::class, SubscribePageFixture::class]);
        $payload = json_encode([
            'title' => 'updated-page@example.org',
            'active' => false,
        ], JSON_THROW_ON_ERROR);

        $this->jsonRequest('PUT', '/api/v2/subscribe-pages/1', content: $payload);
        $this->assertHttpForbidden();
    }

    public function testUpdateSubscribePageWithSessionReturnsOk(): void
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            AdministratorTokenFixture::class,
            SubscribePageFixture::class,
        ]);
        $payload = json_encode([
            'title' => 'updated-page@example.org',
            'active' => false,
        ], JSON_THROW_ON_ERROR);

        $this->authenticatedJsonRequest('PUT', '/api/v2/subscribe-pages/1', content: $payload);

        $this->assertHttpOkay();
        $data = $this->getDecodedJsonResponseContent();
        self::assertSame(1, $data['id']);
        self::assertSame('updated-page@example.org', $data['title']);
        self::assertFalse($data['active']);
        self::assertIsArray($data['owner']);
    }

    public function testUpdateSubscribePageWithSessionNotFound(): void
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            AdministratorTokenFixture::class,
            SubscribePageFixture::class,
        ]);
        $payload = json_encode([
            'title' => 'updated-page@example.org',
            'active' => false,
        ], JSON_THROW_ON_ERROR);

        $this->authenticatedJsonRequest('PUT', '/api/v2/subscribe-pages/9999', content: $payload);
        $this->assertHttpNotFound();
    }

    public function testDeleteSubscribePageWithoutSessionReturnsForbidden(): void
    {
        $this->loadFixtures([AdministratorFixture::class, SubscribePageFixture::class]);
        $this->jsonRequest('DELETE', '/api/v2/subscribe-pages/1');
        $this->assertHttpForbidden();
    }

    public function testDeleteSubscribePageWithSessionReturnsNoContentAndRemovesResource(): void
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            AdministratorTokenFixture::class,
            SubscribePageFixture::class,
        ]);

        $this->authenticatedJsonRequest('DELETE', '/api/v2/subscribe-pages/1');
        $this->assertHttpNoContent();

        $this->authenticatedJsonRequest('GET', '/api/v2/subscribe-pages/1');
        $this->assertHttpNotFound();
    }

    public function testDeleteSubscribePageWithSessionNotFound(): void
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            AdministratorTokenFixture::class,
            SubscribePageFixture::class,
        ]);

        $this->authenticatedJsonRequest('DELETE', '/api/v2/subscribe-pages/9999');
        $this->assertHttpNotFound();
    }

    public function testGetSubscribePageDataWithoutSessionReturnsForbidden(): void
    {
        $this->loadFixtures([AdministratorFixture::class, SubscribePageFixture::class]);
        $this->jsonRequest('GET', '/api/v2/subscribe-pages/1/data');
        $this->assertHttpForbidden();
    }

    public function testGetSubscribePageDataWithSessionReturnsArray(): void
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            AdministratorTokenFixture::class,
            SubscribePageFixture::class,
        ]);

        $this->authenticatedJsonRequest('GET', '/api/v2/subscribe-pages/1/data');
        $this->assertHttpOkay();
        $data = $this->getDecodedJsonResponseContent();
        self::assertIsArray($data);

        if (!empty($data)) {
            self::assertArrayHasKey('id', $data[0]);
            self::assertArrayHasKey('name', $data[0]);
            self::assertArrayHasKey('data', $data[0]);
        }
    }

    public function testGetSubscribePageDataWithSessionNotFound(): void
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            AdministratorTokenFixture::class,
            SubscribePageFixture::class,
        ]);

        $this->authenticatedJsonRequest('GET', '/api/v2/subscribe-pages/9999/data');
        $this->assertHttpNotFound();
    }

    public function testSetSubscribePageDataWithoutSessionReturnsForbidden(): void
    {
        $this->loadFixtures([AdministratorFixture::class, SubscribePageFixture::class]);
        $payload = json_encode([
            'name' => 'intro_text',
            'value' => 'Hello world',
        ], JSON_THROW_ON_ERROR);

        $this->jsonRequest('PUT', '/api/v2/subscribe-pages/1/data', content: $payload);
        $this->assertHttpForbidden();
    }

    public function testSetSubscribePageDataWithMissingNameReturnsUnprocessableEntity(): void
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            AdministratorTokenFixture::class,
            SubscribePageFixture::class,
        ]);
        $payload = json_encode([
            'value' => 'Hello world',
        ], JSON_THROW_ON_ERROR);

        $this->authenticatedJsonRequest('PUT', '/api/v2/subscribe-pages/1/data', content: $payload);
        $this->assertHttpUnprocessableEntity();
    }

    public function testSetSubscribePageDataWithSessionReturnsOk(): void
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            AdministratorTokenFixture::class,
            SubscribePageFixture::class,
        ]);
        $payload = json_encode([
            'name' => 'intro_text',
            'value' => 'Hello world',
        ], JSON_THROW_ON_ERROR);

        $this->authenticatedJsonRequest('PUT', '/api/v2/subscribe-pages/1/data', content: $payload);
        $this->assertHttpOkay();
        $data = $this->getDecodedJsonResponseContent();
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('name', $data);
        self::assertArrayHasKey('data', $data);
        self::assertSame('intro_text', $data['name']);
        self::assertSame('Hello world', $data['data']);
    }

    public function testSetSubscribePageDataWithSessionNotFound(): void
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            AdministratorTokenFixture::class,
            SubscribePageFixture::class,
        ]);
        $payload = json_encode([
            'name' => 'intro_text',
            'value' => 'Hello world',
        ], JSON_THROW_ON_ERROR);

        $this->authenticatedJsonRequest('PUT', '/api/v2/subscribe-pages/9999/data', content: $payload);
        $this->assertHttpNotFound();
    }
}
