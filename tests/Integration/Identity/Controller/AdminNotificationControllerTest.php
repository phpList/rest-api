<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Identity\Controller;

use PhpList\RestBundle\Identity\Controller\AdminNotificationController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorFixture;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorTokenFixture;

class AdminNotificationControllerTest extends AbstractTestController
{
    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(
            AdminNotificationController::class,
            self::getContainer()->get(AdminNotificationController::class)
        );
    }

    public function testGetNotificationsWithoutSessionKeyReturnsUnauthorized(): void
    {
        self::getClient()->request('GET', '/api/v2/notifications');

        $this->assertHttpUnauthorized();
    }

    public function testGetNotificationsWithValidSessionReturnsItems(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class]);

        $this->authenticatedJsonRequest('GET', '/api/v2/notifications');

        $this->assertHttpOkay();
        $content = $this->getDecodedJsonResponseContent();
        self::assertArrayHasKey('items', $content);
        self::assertIsArray($content['items']);
    }
}
