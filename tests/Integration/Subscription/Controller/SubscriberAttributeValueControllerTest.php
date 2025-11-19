<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Subscription\Controller;

use PhpList\RestBundle\Subscription\Controller\SubscriberAttributeValueController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Subscription\Fixtures\SubscriberAttributeDefinitionFixture;
use PhpList\RestBundle\Tests\Integration\Subscription\Fixtures\SubscriberAttributeValueFixture;
use PhpList\RestBundle\Tests\Integration\Subscription\Fixtures\SubscriberFixture;

class SubscriberAttributeValueControllerTest extends AbstractTestController
{
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
            SubscriberAttributeDefinitionFixture::class,
        ]);

        $subscriberId = 1;
        $definitionId = 1;
        $json = json_encode(['value' => 'Test Country']);

        $this->authenticatedJsonRequest(
            'post',
            '/api/v2/subscribers/' . $subscriberId . '/attributes/' . $definitionId,
            [],
            [],
            [],
            $json
        );

        $this->assertHttpCreated();
        $response = $this->getDecodedJsonResponseContent();
        self::assertSame('Test Country', $response['value']);
    }

    public function testDeleteAttributeValue(): void
    {
        $this->loadFixtures([
            SubscriberFixture::class,
            SubscriberAttributeValueFixture::class,
        ]);

        $this->authenticatedJsonRequest(
            'delete',
            '/api/v2/subscribers/1/attributes/1'
        );

        $this->assertHttpNoContent();
    }

    public function testGetPaginatedAttributes(): void
    {
        $this->loadFixtures([
            SubscriberFixture::class,
            SubscriberAttributeDefinitionFixture::class,
        ]);

        $this->authenticatedJsonRequest(
            'get',
            '/api/v2/subscribers/1/attributes'
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
            '/api/v2/subscribers/999/attributes/999'
        );

        $this->assertHttpNotFound();
    }
}
