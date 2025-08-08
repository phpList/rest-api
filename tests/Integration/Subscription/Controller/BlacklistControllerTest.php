<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Subscription\Controller;

use PhpList\RestBundle\Subscription\Controller\BlacklistController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Integration tests for the BlacklistController.
 */
class BlacklistControllerTest extends AbstractTestController
{
    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(
            BlacklistController::class,
            self::getContainer()->get(BlacklistController::class)
        );
    }

    public function testCheckEmailBlacklistedWithoutSessionKeyReturnsForbiddenStatus(): void
    {
        $this->jsonRequest('get', '/api/v2/blacklist/check/test@example.com');

        $this->assertHttpForbidden();
    }

    public function testAddEmailToBlacklistWithoutSessionKeyReturnsForbiddenStatus(): void
    {
        $this->jsonRequest('post', '/api/v2/blacklist/add');

        $this->assertHttpForbidden();
    }

    public function testAddEmailToBlacklistWithMissingEmailReturnsUnprocessableEntityStatus(): void
    {
        $jsonData = [];

        $this->authenticatedJsonRequest('post', '/api/v2/blacklist/add', [], [], [], json_encode($jsonData));

        $this->assertHttpUnprocessableEntity();
    }

    public function testRemoveEmailFromBlacklistWithoutSessionKeyReturnsForbiddenStatus(): void
    {
        $this->jsonRequest('delete', '/api/v2/blacklist/remove/test@example.com');

        $this->assertHttpForbidden();
    }

    public function testGetBlacklistInfoWithoutSessionKeyReturnsForbiddenStatus(): void
    {
        $this->jsonRequest('get', '/api/v2/blacklist/info/test@example.com');

        $this->assertHttpForbidden();
    }
}
