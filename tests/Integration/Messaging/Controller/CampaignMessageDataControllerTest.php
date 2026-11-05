<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Messaging\Controller;

use PhpList\RestBundle\Messaging\Controller\CampaignMessageDataController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorFixture;
use PhpList\RestBundle\Tests\Integration\Messaging\Fixtures\MessageFixture;
use PhpList\RestBundle\Tests\Integration\Subscription\Fixtures\SubscriberListFixture;

class CampaignMessageDataControllerTest extends AbstractTestController
{
    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(
            CampaignMessageDataController::class,
            self::getContainer()->get(CampaignMessageDataController::class)
        );
    }

    public function testUpdateMessageDataWithoutSessionReturnsUnauthorized(): void
    {
        $this->loadFixtures([MessageFixture::class]);

        $this->jsonRequest('PATCH', '/api/v2/campaigns/1/message-data', [], [], [], json_encode([
            'google_track' => true,
        ]));
        $this->assertHttpUnauthorized();
    }

    public function testUpdateMessageDataWithInvalidIdReturnsNotFound(): void
    {
        $this->loadFixtures([AdministratorFixture::class]);

        $this->authenticatedJsonRequest('PATCH', '/api/v2/campaigns/999/message-data', [], [], [], json_encode([
            'google_track' => true,
        ]));
        $this->assertHttpNotFound();
    }

    public function testUpdateMessageDataStoresTheSettingsAndReturnsThem(): void
    {
        $this->loadFixtures([AdministratorFixture::class, MessageFixture::class, SubscriberListFixture::class]);

        $this->authenticatedJsonRequest('PATCH', '/api/v2/campaigns/1/message-data', [], [], [], json_encode([
            'google_track' => true,
            'utm_source' => 'newsletter',
            'notify_start' => 'a@example.com, b@example.com',
            'exclude_lists' => [1],
        ]));
        $this->assertHttpOkay();

        $response = $this->getDecodedJsonResponseContent();
        self::assertTrue($response['google_track']);
        self::assertSame('newsletter', $response['utm_source']);
        self::assertSame('a@example.com, b@example.com', $response['notify_start']);
        self::assertSame([1], $response['exclude_lists']);
    }

    public function testUpdateMessageDataOnlyChangesTheFieldsThatAreSent(): void
    {
        $this->loadFixtures([AdministratorFixture::class, MessageFixture::class]);

        $this->authenticatedJsonRequest('PATCH', '/api/v2/campaigns/1/message-data', [], [], [], json_encode([
            'utm_source' => 'newsletter',
            'utm_medium' => 'email',
        ]));
        $this->authenticatedJsonRequest('PATCH', '/api/v2/campaigns/1/message-data', [], [], [], json_encode([
            'utm_medium' => '',
        ]));
        $this->assertHttpOkay();

        $response = $this->getDecodedJsonResponseContent();
        self::assertSame('newsletter', $response['utm_source']);
        self::assertSame('', $response['utm_medium']);
    }

    public function testUpdateMessageDataWithInvalidValuesReturnsUnprocessableEntity(): void
    {
        $this->loadFixtures([AdministratorFixture::class, MessageFixture::class]);

        $this->authenticatedJsonRequest('PATCH', '/api/v2/campaigns/1/message-data', [], [], [], json_encode([
            'notify_end' => 'not-an-email',
        ]));
        $this->assertHttpUnprocessableEntity();
    }

    public function testUpdateMessageDataWithUnknownExcludedListReturnsNotFound(): void
    {
        $this->loadFixtures([AdministratorFixture::class, MessageFixture::class]);

        $this->authenticatedJsonRequest('PATCH', '/api/v2/campaigns/1/message-data', [], [], [], json_encode([
            'exclude_lists' => [999],
        ]));
        $this->assertHttpNotFound();
    }
}
