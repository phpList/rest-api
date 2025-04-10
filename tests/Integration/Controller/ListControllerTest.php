<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Controller;

use PhpList\Core\Domain\Repository\Subscription\SubscriberListRepository;
use PhpList\RestBundle\Controller\ListController;
use PhpList\RestBundle\Tests\Integration\Controller\Fixtures\AdministratorFixture;
use PhpList\RestBundle\Tests\Integration\Controller\Fixtures\AdministratorTokenFixture;
use PhpList\RestBundle\Tests\Integration\Controller\Fixtures\SubscriberFixture;
use PhpList\RestBundle\Tests\Integration\Controller\Fixtures\SubscriberListFixture;
use PhpList\RestBundle\Tests\Integration\Controller\Fixtures\SubscriptionFixture;

/**
 * Testcase.
 *
 * @author Oliver Klee <oliver@phplist.com>
 * @author Xheni Myrtaj <xheni@phplist.com>
 */
class ListControllerTest extends AbstractTestController
{
    public function testControllerIsAvailableViaContainer()
    {
        self::assertInstanceOf(ListController::class, self::getContainer()->get(ListController::class));
    }

    public function testGetListsWithoutSessionKeyReturnsForbiddenStatus()
    {
        self::getClient()->request('get', '/api/v2/lists');

        $this->assertHttpForbidden();
    }

    public function testGetListsWithExpiredSessionKeyReturnsForbiddenStatus()
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class]);

        self::getClient()->request(
            'get',
            '/api/v2/lists',
            [],
            [],
            ['PHP_AUTH_USER' => 'unused', 'PHP_AUTH_PW' => 'cfdf64eecbbf336628b0f3071adba763']
        );

        $this->assertHttpForbidden();
    }

    public function testGetListsWithCurrentSessionKeyReturnsOkayStatus()
    {
        $this->authenticatedJsonRequest('get', '/api/v2/lists');

        $this->assertHttpOkay();
    }

    public function testGetListsWithCurrentSessionKeyReturnsListData()
    {
        $this->loadFixtures([SubscriberListFixture::class]);

        $this->authenticatedJsonRequest('get', '/api/v2/lists');

        $this->assertJsonResponseContentEquals(
            [
                [
                    'id' => 1,
                    'name' => 'News',
                    'creation_date' => '2016-06-22T15:01:17+00:00',
                    'description' => 'News (and some fun stuff)',
                    'list_position' => 12,
                    'subject_prefix' => 'phpList',
                    'public' => true,
                    'category' => 'news',
                ],
                [
                    'id' => 2,
                    'name' => 'More news',
                    'creation_date' => '2016-06-22T15:01:17+00:00',
                    'description' => '',
                    'list_position' => 12,
                    'subject_prefix' => '',
                    'public' => true,
                    'category' => '',
                ],
                [
                    'id' => 3,
                    'name' => 'Tech news',
                    'creation_date' => '2019-02-11T15:01:15+00:00',
                    'description' => '',
                    'list_position' => 12,
                    'subject_prefix' => '',
                    'public' => true,
                    'category' => '',
                ],
            ]
        );
    }

    public function testGetListWithoutSessionKeyForExistingListReturnsForbiddenStatus()
    {
        $this->loadFixtures([SubscriberListFixture::class]);

        self::getClient()->request('get', '/api/v2/lists/1');

        $this->assertHttpForbidden();
    }

    public function testGetListWithCurrentSessionKeyForExistingListReturnsOkayStatus()
    {
        $this->loadFixtures([SubscriberListFixture::class]);

        $this->authenticatedJsonRequest('get', '/api/v2/lists/1');

        $this->assertHttpOkay();
    }

    public function testGetListWithCurrentSessionKeyForInexistentListReturnsNotFoundStatus()
    {
        $this->authenticatedJsonRequest('get', '/api/v2/lists/999');

        $this->assertHttpNotFound();
    }

    public function testGetListWithCurrentSessionKeyReturnsListData()
    {
        $this->loadFixtures([SubscriberListFixture::class]);

        $this->authenticatedJsonRequest('get', '/api/v2/lists/1');

        $this->assertJsonResponseContentEquals(
            [
                'id' => 1,
                'name' => 'News',
                'creation_date' => '2016-06-22T15:01:17+00:00',
                'description' => 'News (and some fun stuff)',
                'list_position' => 12,
                'subject_prefix' => 'phpList',
                'public' => true,
                'category' => 'news',
            ]
        );
    }

    public function testDeleteListWithoutSessionKeyForExistingListReturnsForbiddenStatus()
    {
        $this->loadFixtures([SubscriberListFixture::class]);

        self::getClient()->request('delete', '/api/v2/lists/1');

        $this->assertHttpForbidden();
    }

    public function testDeleteListWithCurrentSessionKeyForExistingListReturnsNoContentStatus()
    {
        $this->loadFixtures([SubscriberFixture::class, SubscriberListFixture::class, SubscriptionFixture::class]);

        $this->authenticatedJsonRequest('delete', '/api/v2/lists/1');

        $this->assertHttpNoContent();
    }

    public function testDeleteListWithCurrentSessionKeyForInexistentListReturnsNotFoundStatus()
    {
        $this->authenticatedJsonRequest('delete', '/api/v2/lists/999');

        $this->assertHttpNotFound();
    }

    public function testDeleteListWithCurrentSessionKeyDeletesList()
    {
        $this->loadFixtures([SubscriberListFixture::class]);

        $this->authenticatedJsonRequest('delete', '/api/v2/lists/1');

        $listRepository = self::getContainer()->get(SubscriberListRepository::class);
        self::assertNull($listRepository->find(1));
    }

    public function testGetListMembersForExistingListWithoutSessionKeyReturnsForbiddenStatus()
    {
        $this->loadFixtures([SubscriberListFixture::class]);

        self::getClient()->request('get', '/api/v2/lists/1/subscribers');

        $this->assertHttpForbidden();
    }

    public function testGetListMembersForExistingListWithExpiredSessionKeyReturnsForbiddenStatus()
    {
        $this->loadFixtures([
            SubscriberListFixture::class,
            AdministratorFixture::class,
            AdministratorTokenFixture::class,
        ]);

        self::getClient()->request(
            'get',
            '/api/v2/lists/1/subscribers',
            [],
            [],
            ['PHP_AUTH_USER' => 'unused', 'PHP_AUTH_PW' => 'cfdf64eecbbf336628b0f3071adba763']
        );

        $this->assertHttpForbidden();
    }

    public function testGetListMembersWithCurrentSessionKeyForInexistentListReturnsNotFoundStatus()
    {
        $this->authenticatedJsonRequest('get', '/api/v2/lists/999/subscribers');

        $this->assertHttpNotFound();
    }

    public function testGetListMembersWithCurrentSessionKeyForExistingListReturnsOkayStatus()
    {
        $this->loadFixtures([SubscriberListFixture::class]);

        $this->authenticatedJsonRequest('get', '/api/v2/lists/1/subscribers');

        $this->assertHttpOkay();
    }

    public function testGetListMembersWithCurrentSessionKeyForExistingListWithoutSubscribersReturnsEmptyArray()
    {
        $this->loadFixtures([SubscriberListFixture::class]);

        $this->authenticatedJsonRequest('get', '/api/v2/lists/1/subscribers');

        $this->assertJsonResponseContentEquals([]);
    }

    public function testGetListMembersWithCurrentSessionKeyForExistingListWithSubscribersReturnsSubscribers()
    {
        $this->loadFixtures([SubscriberListFixture::class, SubscriberFixture::class, SubscriptionFixture::class]);

        $this->authenticatedJsonRequest('get', '/api/v2/lists/2/subscribers');

        $this->assertJsonResponseContentEquals(
            [
                [
                    'id' => 1,
                    'email' => 'oliver@example.com',
                    'creation_date' => '2016-07-22T15:01:17+00:00',
                    'confirmed' => true,
                    'blacklisted' => true,
                    'bounce_count' => 17,
                    'unique_id' => '95feb7fe7e06e6c11ca8d0c48cb46e89',
                    'html_email' => true,
                    'disabled' => true,
                    'subscribedLists' => [
                        [
                            'id' => 2,
                            'name' => 'More news',
                            'description' => '',
                            'creation_date' => '2016-06-22T15:01:17+00:00',
                            'public' => true,
                        ],
                    ],
                ], [
                    'id' => 2,
                    'email' => 'oliver1@example.com',
                    'creation_date' => '2016-07-22T15:01:17+00:00',
                    'confirmed' => true,
                    'blacklisted' => true,
                    'bounce_count' => 17,
                    'unique_id' => '95feb7fe7e06e6c11ca8d0c48cb46e87',
                    'html_email' => true,
                    'disabled' => true,
                    'subscribedLists' => [
                        [
                            'id' => 2,
                            'name' => 'More news',
                            'description' => '',
                            'creation_date' => '2016-06-22T15:01:17+00:00',
                            'public' => true,
                        ],
                        [
                            'id' => 1,
                            'name' => 'News',
                            'description' => 'News (and some fun stuff)',
                            'creation_date' => '2016-06-22T15:01:17+00:00',
                            'public' => true,
                        ],
                    ],
                ],
            ]
        );
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
        $responseContent = $this->getResponseContentAsInt();

        self::assertSame(0, $responseContent);
    }

    public function testGetSubscribersCountForListWithValidSession()
    {
        $this->loadFixtures([SubscriberListFixture::class, SubscriberFixture::class, SubscriptionFixture::class]);

        $this->authenticatedJsonRequest('get', '/api/v2/lists/2/subscribers/count');
        $responseContent = $this->getResponseContentAsInt();

        self::assertSame(2, $responseContent);
    }
}
