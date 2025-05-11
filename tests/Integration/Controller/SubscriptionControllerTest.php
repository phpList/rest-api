<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Controller;

use PhpList\RestBundle\Subscription\Controller\SubscriptionController;
use PhpList\RestBundle\Tests\Integration\Controller\Fixtures\Identity\AdministratorFixture;
use PhpList\RestBundle\Tests\Integration\Controller\Fixtures\Identity\AdministratorTokenFixture;
use PhpList\RestBundle\Tests\Integration\Controller\Fixtures\Messaging\SubscriberListFixture;
use PhpList\RestBundle\Tests\Integration\Controller\Fixtures\Subscription\SubscriberFixture;
use PhpList\RestBundle\Tests\Integration\Controller\Fixtures\Subscription\SubscriptionFixture;

class SubscriptionControllerTest extends AbstractTestController
{
    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(
            SubscriptionController::class,
            self::getContainer()->get(SubscriptionController::class)
        );
    }

    public function testCreateSubscriptionWithValidEmailsReturns201(): void
    {
        $this->loadFixtures([
            SubscriberListFixture::class,
            AdministratorFixture::class,
            AdministratorTokenFixture::class,
            SubscriberFixture::class,
        ]);

        $payload = json_encode(['emails' => ['oliver@example.com']]);

        $this->authenticatedJsonRequest('POST', '/api/v2/lists/1/subscribers', [], [], [], $payload);
        $this->assertHttpCreated();
    }

    public function testDeleteSubscriptionReturnsNoContent(): void
    {
        $this->loadFixtures([SubscriberListFixture::class, SubscriberFixture::class, SubscriptionFixture::class]);

        $this->authenticatedJsonRequest('DELETE', '/api/v2/lists/2/subscribers?emails[]=oliver@example.com');
        $this->assertHttpNoContent();
    }

    public function testDeleteSubscriptionForUnknownEmailReturnsValidationError(): void
    {
        $this->loadFixtures([SubscriberListFixture::class]);

        $this->authenticatedJsonRequest('DELETE', '/api/v2/lists/1/subscribers?emails[]=unknown@example.com');
        $this->assertHttpNotFound();
    }


    public function testGetListSubscribersCountWithCurrentSessionKeyForExistingListReturnsOkayStatus()
    {
        $this->loadFixtures([SubscriberListFixture::class]);

        $this->authenticatedJsonRequest('get', '/api/v2/lists/1/subscribers/count');

        $this->assertHttpOkay();
    }
}
