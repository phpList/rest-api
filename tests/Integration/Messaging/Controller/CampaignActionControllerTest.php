<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Messaging\Controller;

use PhpList\Core\Domain\Messaging\Model\Message;
use PhpList\RestBundle\Messaging\Controller\CampaignActionController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorFixture;
use PhpList\RestBundle\Tests\Integration\Messaging\Fixtures\MessageFixture;
use PhpList\RestBundle\Tests\Integration\Subscription\Fixtures\SubscriberFixture;
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

    public function testSendMessageWithoutSessionReturnsUnauthorized(): void
    {
        $this->loadFixtures([MessageFixture::class]);
        self::getClient()->request('POST', '/api/v2/campaigns/1/send');
        $this->assertHttpUnauthorized();
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

    public function testResendMessageToListsWithoutSessionReturnsUnauthorized(): void
    {
        $this->loadFixtures([MessageFixture::class, SubscriberListFixture::class]);

        $this->jsonRequest('POST', '/api/v2/campaigns/2/resend', [], [], [], json_encode(['list_ids' => [1]]));
        $this->assertHttpUnauthorized();
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

    public function testUpdateMessageStatusWithInvalidMessageReturnsUnprocessableEntity(): void
    {
        $this->loadFixtures([AdministratorFixture::class, MessageFixture::class]);
        $this->makeMessageInvalid(2);

        $this->authenticatedJsonRequest('PATCH', '/api/v2/campaigns/2/status', [], [], [], json_encode([
            'status' => 'suspended',
        ]));
        $this->assertHttpUnprocessableEntity();

        $response = $this->getDecodedJsonResponseContent();
        self::assertArrayHasKey('options.from_field', $response['errors']);
    }

    public function testSendMessageWithInvalidMessageReturnsUnprocessableEntity(): void
    {
        $this->loadFixtures([AdministratorFixture::class, MessageFixture::class]);
        $this->makeMessageInvalid(2);

        $this->authenticatedJsonRequest('POST', '/api/v2/campaigns/2/send');
        $this->assertHttpUnprocessableEntity();
    }

    public function testResendMessageToListsWithInvalidMessageReturnsUnprocessableEntity(): void
    {
        $this->loadFixtures([AdministratorFixture::class, MessageFixture::class, SubscriberListFixture::class]);
        $this->makeMessageInvalid(2);

        $this->authenticatedJsonRequest('POST', '/api/v2/campaigns/2/resend', [], [], [], json_encode([
            'list_ids' => [1],
        ]));
        $this->assertHttpUnprocessableEntity();
    }

    public function testTestSendMessageWithInvalidMessageReturnsUnprocessableEntity(): void
    {
        $this->loadFixtures([AdministratorFixture::class, MessageFixture::class, SubscriberFixture::class]);
        $this->makeMessageInvalid(2);

        $this->authenticatedJsonRequest('POST', '/api/v2/campaigns/2/test-send', [], [], [], json_encode([
            'emails' => ['oliver@example.com'],
        ]));
        $this->assertHttpUnprocessableEntity();
    }

    private function makeMessageInvalid(int $messageId): void
    {
        $message = $this->entityManager->find(Message::class, $messageId);
        $message->getOptions()->setFromField('not-an-email');
        $this->entityManager->flush();
    }
}
