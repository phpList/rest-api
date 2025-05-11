<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Controller;

use PhpList\Core\Domain\Subscription\Repository\SubscriberAttributeDefinitionRepository;
use PhpList\RestBundle\Subscription\Controller\SubscriberAttributeDefinitionController;
use PhpList\RestBundle\Tests\Integration\Controller\Fixtures\Identity\AdministratorFixture;
use PhpList\RestBundle\Tests\Integration\Controller\Fixtures\Identity\AdministratorTokenFixture;
use PhpList\RestBundle\Tests\Integration\Controller\Fixtures\Subscription\AttributeDefinitionFixture;

class AttributeDefinitionControllerTest extends AbstractTestController
{
    public function testControllerIsAvailableViaContainer()
    {
        self::assertInstanceOf(
            SubscriberAttributeDefinitionController::class,
            self::getContainer()->get(SubscriberAttributeDefinitionController::class)
        );
    }

    public function testGetAttributesWithoutSessionKeyReturnsForbidden()
    {
        self::getClient()->request('GET', '/api/v2/attributes');
        $this->assertHttpForbidden();
    }

    public function testGetAttributesWithSessionKeyReturnsOk()
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class]);
        $this->authenticatedJsonRequest('GET', '/api/v2/attributes');
        $this->assertHttpOkay();
    }

    public function testGetAttributeWithInvalidIdReturnsNotFound()
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class]);
        $this->authenticatedJsonRequest('GET', '/api/v2/attributes/999');
        $this->assertHttpNotFound();
    }

    public function testCreateAttributeDefinition()
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class]);

        $payload = json_encode([
            'name' => 'Country',
            'type' => 'checkbox',
            'order' => 12,
            'default_value' => 'United States',
            'required' => true,
            'table_name' => 'list_attributes',
        ]);

        $this->authenticatedJsonRequest('POST', '/api/v2/attributes', [], [], [], $payload);

        $this->assertHttpCreated();

        $response = $this->getDecodedJsonResponseContent();
        self::assertSame('Country', $response['name']);
    }

    public function testUpdateAttributeDefinition()
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            AdministratorTokenFixture::class,
            AttributeDefinitionFixture::class,
        ]);

        $payload = json_encode([
            'name' => 'Updated Country',
            'type' => 'checkbox',
            'order' => 10,
            'default_value' => 'Canada',
            'required' => false,
            'table_name' => 'list_attributes',
        ]);

        $this->authenticatedJsonRequest('PUT', '/api/v2/attributes/1', [], [], [], $payload);
        $this->assertHttpOkay();
        $response = $this->getDecodedJsonResponseContent();
        self::assertSame('Updated Country', $response['name']);
    }

    public function testDeleteAttributeDefinition()
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            AdministratorTokenFixture::class,
            AttributeDefinitionFixture::class,
        ]);

        $this->authenticatedJsonRequest('DELETE', '/api/v2/attributes/1');
        $this->assertHttpNoContent();

        $repo = self::getContainer()->get(SubscriberAttributeDefinitionRepository::class);
        self::assertNull($repo->find(1));
    }

    public function testCreateAttributeDefinitionMissingNameReturnsValidationError(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class]);

        $payload = json_encode([
            'type' => 'text',
            'order' => 1,
            'required' => false
        ]);

        $this->authenticatedJsonRequest('POST', '/api/v2/attributes', [], [], [], $payload);
        $this->assertHttpUnprocessableEntity();
    }
}
