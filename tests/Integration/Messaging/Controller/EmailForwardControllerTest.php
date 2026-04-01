<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Messaging\Controller;

use PhpList\RestBundle\Messaging\Controller\EmailForwardController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorFixture;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorTokenFixture;
use PhpList\RestBundle\Tests\Integration\Messaging\Fixtures\MessageFixture;
use Symfony\Component\HttpFoundation\Response;

class EmailForwardControllerTest extends AbstractTestController
{
    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(
            EmailForwardController::class,
            self::getContainer()->get(EmailForwardController::class)
        );
    }

    public function testForwardWithInvalidPayloadReturnsUnprocessableEntity(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class, MessageFixture::class]);

        // Missing required 'recipients' field should trigger 422 from RequestValidator
        $this->authenticatedJsonRequest('POST', '/api/v2/email-forward/1', content: json_encode([ ]));

        $this->assertHttpUnprocessableEntity();
    }

    public function testForwardWithValidDataButNotReceivedEmail(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class, MessageFixture::class]);

        $payload = json_encode([
            'recipients' => ['friend1@example.com'],
            'uid' => 'fwd-123',
            'note' => null,
            'from_name' => 'Alice',
            'from_email' => 'alice@example.com',
        ]);

        $this->authenticatedJsonRequest('POST', '/api/v2/email-forward/1', content: $payload);

        $response = self::getClient()->getResponse();
        $this->assertHttpUnprocessableEntity();
        self::assertStringContainsString('application/json', (string)$response->headers);

        $data = $this->getDecodedJsonResponseContent();
        self::assertIsArray($data);
        self::assertArrayHasKey('message', $data);
        self::assertStringContainsString('Cannot forward: user has not received this message', $data['message']);
    }

    public function testForwardWithInvalidEmailInRecipientsReturnsUnprocessableEntity(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class, MessageFixture::class]);

        $payload = json_encode([
            'recipients' => ['not-an-email'],
        ]);

        $this->authenticatedJsonRequest('POST', '/api/v2/email-forward/1', content: $payload);

        $this->assertHttpUnprocessableEntity();
    }

    public function testForwardWithInvalidIdReturnsNotFound(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class]);

        $this->authenticatedJsonRequest('POST', '/api/v2/email-forward/9999', content: json_encode([
            'recipients' => ['friend@example.com'],
        ]));

        $this->assertHttpNotFound();
    }
}
