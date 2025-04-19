<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Controller;

use PhpList\RestBundle\Controller\SubscriptionController;
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

    public function testGetSubscribersWithoutSessionReturnsForbidden(): void
    {
        $this->loadFixtures([SubscriberListFixture::class]);
        self::getClient()->request('GET', '/api/v2/lists/1/subscribers');
        $this->assertHttpForbidden();
    }

    public function testGetSubscribersWithSessionReturnsList(): void
    {
        $this->loadFixtures([SubscriberListFixture::class, SubscriberFixture::class, SubscriptionFixture::class]);
        $this->authenticatedJsonRequest('GET', '/api/v2/lists/2/subscribers');
        $this->assertHttpOkay();
    }

    public function testGetSubscribersCountWithSessionReturnsCorrectCount(): void
    {
        $this->loadFixtures([SubscriberListFixture::class, SubscriberFixture::class, SubscriptionFixture::class]);
        $this->authenticatedJsonRequest('GET', '/api/v2/lists/2/subscribers/count');
        $data = $this->getDecodedJsonResponseContent();
        self::assertSame(2, $data['subscribers_count']);
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

        $this->authenticatedJsonRequest('POST', '/api/v2/lists/1/subscribers',  [], [], [], $payload);
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


    public function testGetListSubscribersCountForExistingListWithoutSessionKeyReturnsForbiddenStatus()
    {
        $this->loadFixtures([SubscriberListFixture::class]);

        self::getClient()->request('get', '/api/v2/lists/1/subscribers/count');

        $this->assertHttpForbidden();
    }

    public function testGetListSubscribersCountForExistingListWithExpiredSessionKeyReturnsForbiddenStatus()
    {
        $this->loadFixtures([
            SubscriberListFixture::class,
            AdministratorFixture::class,
            AdministratorTokenFixture::class,
        ]);

        self::getClient()->request(
            'get',
            '/api/v2/lists/1/subscribers/count',
            [],
            [],
            ['PHP_AUTH_USER' => 'unused', 'PHP_AUTH_PW' => 'cfdf64eecbbf336628b0f3071adba764']
        );

        $this->assertHttpForbidden();
    }

    public function testGetListSubscribersCountWithCurrentSessionKeyForExistingListReturnsOkayStatus()
    {
        $this->loadFixtures([SubscriberListFixture::class]);

        $this->authenticatedJsonRequest('get', '/api/v2/lists/1/subscribers/count');

        $this->assertHttpOkay();
    }

    public function testGetSubscribersCountForEmptyListWithValidSession()
    {
        $this->loadFixtures([SubscriberListFixture::class, SubscriberFixture::class, SubscriptionFixture::class]);

        $this->authenticatedJsonRequest('get', '/api/v2/lists/3/subscribers/count');
        $responseData = $this->getDecodedJsonResponseContent();

        self::assertSame(0, $responseData['subscribers_count']);
    }

    public function testGetSubscribersCountForListWithValidSession()
    {
        $this->loadFixtures([SubscriberListFixture::class, SubscriberFixture::class, SubscriptionFixture::class]);

        $this->authenticatedJsonRequest('get', '/api/v2/lists/2/subscribers/count');
        $responseData = $this->getDecodedJsonResponseContent();

        self::assertSame(2, $responseData['subscribers_count']);
    }
}
