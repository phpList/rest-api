<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Messaging\Controller;

use PhpList\RestBundle\Messaging\Controller\CampaignActionController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorFixture;
use PhpList\RestBundle\Tests\Integration\Messaging\Fixtures\MessageFixture;
use PhpList\RestBundle\Tests\Integration\Subscription\Fixtures\SubscriberListFixture;

class CampaignActionControllerTest extends AbstractTestController
{
    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(
            CampaignActionController::class,
            self::getContainer()->get(CampaignActionController::class)
        );
    }

    public function testSendMessageWithoutSessionReturnsForbidden(): void
    {
        $this->loadFixtures([MessageFixture::class]);
        self::getClient()->request('POST', '/api/v2/campaigns/1/send');
        $this->assertHttpForbidden();
    }

    public function testSendMessageWithValidSessionReturnsOkay(): void
    {
        $this->loadFixtures([AdministratorFixture::class, MessageFixture::class]);

        $this->authenticatedJsonRequest('POST', '/api/v2/campaigns/2/send');
        $this->assertHttpOkay();

        $response = $this->getDecodedJsonResponseContent();
        self::assertSame(2, $response['id']);
    }

    public function testSendMessageWithInvalidIdReturnsNotFound(): void
    {
        $this->authenticatedJsonRequest('POST', '/api/v2/campaigns/999/send');
        $this->assertHttpNotFound();
    }

    public function testResendMessageToListsWithoutSessionReturnsForbidden(): void
    {
        $this->loadFixtures([MessageFixture::class, SubscriberListFixture::class]);

        $this->jsonRequest('POST', '/api/v2/campaigns/2/resend', [], [], [], json_encode(['list_ids' => [1]]));
        $this->assertHttpForbidden();
    }

    public function testResendMessageToListsWithValidSessionReturnsOkay(): void
    {
        $this->loadFixtures([MessageFixture::class, SubscriberListFixture::class]);

        $this->authenticatedJsonRequest('POST', '/api/v2/campaigns/2/resend', [], [], [], json_encode([
            'list_ids' => [1],
        ]));
        $this->assertHttpOkay();

        $response = $this->getDecodedJsonResponseContent();
        self::assertSame(2, $response['id']);
    }

    public function testResendMessageToListsWithInvalidIdReturnsNotFound(): void
    {
        $this->loadFixtures([SubscriberListFixture::class]);

        $this->authenticatedJsonRequest('POST', '/api/v2/campaigns/999/resend', [], [], [], json_encode([
            'list_ids' => [1],
        ]));
        $this->assertHttpNotFound();
    }
}
