<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Subscription\Controller;

use PhpList\RestBundle\Subscription\Controller\ListMembersController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorFixture;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorTokenFixture;
use PhpList\RestBundle\Tests\Integration\Subscription\Fixtures\SubscriberFixture;
use PhpList\RestBundle\Tests\Integration\Subscription\Fixtures\SubscriberListFixture;
use PhpList\RestBundle\Tests\Integration\Subscription\Fixtures\SubscriptionFixture;

class ListMembersControllerTest extends AbstractTestController
{
    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(
            ListMembersController::class,
            self::getContainer()->get(ListMembersController::class)
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
