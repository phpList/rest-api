<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Controller;

use PhpList\Core\Domain\Messaging\Repository\TemplateRepository;
use PhpList\RestBundle\Controller\TemplateController;
use PhpList\RestBundle\Tests\Integration\Controller\Fixtures\Identity\AdministratorFixture;
use PhpList\RestBundle\Tests\Integration\Controller\Fixtures\Identity\AdministratorTokenFixture;
use PhpList\RestBundle\Tests\Integration\Controller\Fixtures\Messaging\TemplateFixture;

class TemplateControllerTest extends AbstractTestController
{
    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(TemplateController::class, self::getContainer()->get(TemplateController::class));
    }

    public function testGetTemplatesWithoutSessionKeyReturnsForbidden(): void
    {
        self::getClient()->request('GET', '/api/v2/templates');
        $this->assertHttpForbidden();
    }

    public function testGetTemplatesWithExpiredSessionKeyReturnsForbidden(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class]);

        self::getClient()->request(
            'GET',
            '/api/v2/templates',
            [],
            [],
            ['PHP_AUTH_USER' => 'unused', 'PHP_AUTH_PW' => 'expiredtoken']
        );

        $this->assertHttpForbidden();
    }

    public function testGetTemplatesWithValidSessionKeyReturnsOkay(): void
    {
        $this->authenticatedJsonRequest('GET', '/api/v2/templates');
        $this->assertHttpOkay();
    }

    public function testGetTemplatesReturnsTemplateData(): void
    {
        $this->loadFixtures([TemplateFixture::class]);

        $this->authenticatedJsonRequest('GET', '/api/v2/templates');
        $response = $this->getDecodedJsonResponseContent();

        self::assertIsArray($response);
        self::assertArrayHasKey('id', $response['items'][0]);
        self::assertArrayHasKey('title', $response['items'][0]);
    }

    public function testGetTemplateWithoutSessionKeyReturnsForbidden(): void
    {
        $this->loadFixtures([TemplateFixture::class]);

        self::getClient()->request('GET', '/api/v2/templates/1');
        $this->assertHttpForbidden();
    }

    public function testGetTemplateWithValidSessionKeyReturnsOkay(): void
    {
        $this->loadFixtures([TemplateFixture::class]);

        $this->authenticatedJsonRequest('GET', '/api/v2/templates/1');
        $this->assertHttpOkay();
    }

    public function testGetTemplateWithInvalidIdReturnsNotFound(): void
    {
        $this->authenticatedJsonRequest('GET', '/api/v2/templates/999');
        $this->assertHttpNotFound();
    }

    public function testCreateTemplateWithValidDataReturnsCreated(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class]);

        $payload = json_encode([
            'title' => 'New Template',
            'content' => '<html><body>[CONTENT]</body></html>',
            'text' => '[CONTENT]',
            'check_links' => true,
            'check_images' => false,
            'check_external_images' => false,
        ]);

        $this->authenticatedJsonRequest('POST', '/api/v2/templates', [], [], [], $payload);
        $this->assertHttpCreated();
    }

    public function testCreateTemplateMissingTitleReturnsValidationError(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class]);

        $payload = json_encode([
            'content' => '<html><body>[CONTENT]</body></html>',
        ]);

        $this->authenticatedJsonRequest('POST', '/api/v2/templates', [], [], [], $payload);
        $this->assertHttpUnprocessableEntity();
    }

    public function testDeleteTemplateWithValidSessionKeyReturnsNoContent(): void
    {
        $this->loadFixtures([TemplateFixture::class]);

        $this->authenticatedJsonRequest('DELETE', '/api/v2/templates/1');
        $this->assertHttpNoContent();
    }

    public function testDeleteTemplateWithInvalidIdReturnsNotFound(): void
    {
        $this->authenticatedJsonRequest('DELETE', '/api/v2/templates/999');
        $this->assertHttpNotFound();
    }

    public function testDeleteTemplateActuallyDeletes(): void
    {
        $this->loadFixtures([TemplateFixture::class]);

        $this->authenticatedJsonRequest('DELETE', '/api/v2/templates/1');

        $templateRepository = self::getContainer()->get(TemplateRepository::class);
        self::assertNull($templateRepository->find(1));
    }
}
