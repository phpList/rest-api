<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Subscription\Controller;

use PhpList\Core\Domain\Subscription\Repository\SubscriberAttributeValueRepository;
use PhpList\RestBundle\Subscription\Controller\SubscriberAttributeValueController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Subscription\Fixtures\AttributeDefinitionFixture;
use PhpList\RestBundle\Tests\Integration\Subscription\Fixtures\SubscriberFixture;

class SubscriberAttributeValueControllerTest extends AbstractTestController
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->attributeValueRepo = self::getContainer()->get(SubscriberAttributeValueRepository::class);
    }

    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(
            SubscriberAttributeValueController::class,
            self::getContainer()->get(SubscriberAttributeValueController::class)
        );
    }

    public function testCreateOrUpdateAttributeValue(): void
    {
        $this->loadFixtures([
            SubscriberFixture::class,
            AttributeDefinitionFixture::class,
        ]);

        $subscriberId = 1;
        $definitionId = 1;
        $json = json_encode(['value' => 'Test Country']);

        $this->authenticatedJsonRequest(
            'post',
            "/api/v2/subscribers/attribute-values/$subscriberId/$definitionId",
            [],
            [],
            [],
            $json
        );

        $this->assertHttpCreated();
        $response = $this->getDecodedJsonResponseContent();
        self::assertSame('Test Country', $response['value']);
    }

    public function testGetSubscriberAttributeValue(): void
    {
        $this->loadFixtures([
            SubscriberFixture::class,
            AttributeDefinitionFixture::class,
        ]);

        $this->authenticatedJsonRequest(
            'post',
            '/api/v2/subscribers/attribute-values/1/1',
            [],
            [],
            [],
            json_encode(['value' => 'Test City'])
        );

        $this->assertHttpCreated();

        $this->authenticatedJsonRequest(
            'get',
            '/api/v2/subscribers/attribute-values/1/1'
        );

        $this->assertHttpOkay();
        $response = $this->getDecodedJsonResponseContent();
        self::assertSame('Test City', $response['value']);
    }

    public function testDeleteAttributeValue(): void
    {
        $this->loadFixtures([
            SubscriberFixture::class,
            AttributeDefinitionFixture::class,
        ]);

        $this->authenticatedJsonRequest(
            'post',
            '/api/v2/subscribers/attribute-values/1/1',
            [],
            [],
            [],
            json_encode(['value' => 'To Delete'])
        );

        $this->assertHttpCreated();

        $this->authenticatedJsonRequest(
            'delete',
            '/api/v2/subscribers/attribute-values/1/1'
        );

        $this->assertHttpNoContent();
    }

    public function testGetPaginatedAttributes(): void
    {
        $this->loadFixtures([
            SubscriberFixture::class,
            AttributeDefinitionFixture::class,
        ]);

        $this->authenticatedJsonRequest(
            'get',
            '/api/v2/subscribers/attribute-values/1'
        );

        $this->assertHttpOkay();
        $response = $this->getDecodedJsonResponseContent();
        self::assertArrayHasKey('items', $response);
        self::assertArrayHasKey('pagination', $response);
    }

    public function testAttributeValueNotFoundReturns404(): void
    {
        $this->authenticatedJsonRequest(
            'get',
            '/api/v2/subscribers/attribute-values/999/999'
        );

        $this->assertHttpNotFound();
    }
}
