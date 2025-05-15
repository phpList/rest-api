<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Subscription\Controller;

use PhpList\Core\Domain\Subscription\Repository\SubscriberListRepository;
use PhpList\RestBundle\Subscription\Controller\SubscriberListController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorFixture;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorTokenFixture;
use PhpList\RestBundle\Tests\Integration\Subscription\Fixtures\SubscriberFixture;
use PhpList\RestBundle\Tests\Integration\Subscription\Fixtures\SubscriberListFixture;
use PhpList\RestBundle\Tests\Integration\Subscription\Fixtures\SubscriptionFixture;

/**
 * Testcase.
 *
 * @author Oliver Klee <oliver@phplist.com>
 * @author Xheni Myrtaj <xheni@phplist.com>
 */
class SubscriberListControllerTest extends AbstractTestController
{
    public function testControllerIsAvailableViaContainer()
    {
        self::assertInstanceOf(
            SubscriberListController::class,
            self::getContainer()->get(SubscriberListController::class)
        );
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

        $this->assertJsonResponseContentEquals([
            'items' => [
                [
                    'id' => 1,
                    'name' => 'News',
                    'created_at' => '2016-06-22T15:01:17+00:00',
                    'description' => 'News (and some fun stuff)',
                    'list_position' => 12,
                    'subject_prefix' => 'phpList',
                    'public' => true,
                    'category' => 'news',
                ],
                [
                    'id' => 2,
                    'name' => 'More news',
                    'created_at' => '2016-06-22T15:01:17+00:00',
                    'description' => '',
                    'list_position' => 12,
                    'subject_prefix' => '',
                    'public' => true,
                    'category' => '',
                ],
                [
                    'id' => 3,
                    'name' => 'Tech news',
                    'created_at' => '2019-02-11T15:01:15+00:00',
                    'description' => '',
                    'list_position' => 12,
                    'subject_prefix' => '',
                    'public' => true,
                    'category' => '',
                ],
            ],
            'pagination' => [
                'total' => 3,
                'limit' => 25,
                'has_more' => false,
                'next_cursor' => 3,
            ],
        ]);
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
                'created_at' => '2016-06-22T15:01:17+00:00',
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

        $this->assertJsonResponseContentEquals([
            'items' => [],
            'pagination' => [
                'total' => 0,
                'limit' => 25,
                'has_more' => false,
                'next_cursor' => null,
            ]
        ]);
    }

    public function testGetListMembersWithCurrentSessionKeyForExistingListWithSubscribersReturnsSubscribers()
    {
        $this->loadFixtures([SubscriberListFixture::class, SubscriberFixture::class, SubscriptionFixture::class]);

        $this->authenticatedJsonRequest('get', '/api/v2/lists/2/subscribers');

        $this->assertJsonResponseContentEquals(
            [
                'items' => [
                    [
                        'id' => 1,
                        'email' => 'oliver@example.com',
                        'created_at' => '2016-07-22T15:01:17+00:00',
                        'confirmed' => true,
                        'blacklisted' => true,
                        'bounce_count' => 17,
                        'unique_id' => '95feb7fe7e06e6c11ca8d0c48cb46e89',
                        'html_email' => true,
                        'disabled' => true,
                        'subscribed_lists' => [
                            [
                                'id' => 2,
                                'name' => 'More news',
                                'description' => '',
                                'created_at' => '2016-06-22T15:01:17+00:00',
                                'public' => true,
                                'subscription_date' => '2016-07-22T15:01:17+00:00',
                            ],
                        ],
                    ], [
                        'id' => 2,
                        'email' => 'oliver1@example.com',
                        'created_at' => '2016-07-22T15:01:17+00:00',
                        'confirmed' => true,
                        'blacklisted' => true,
                        'bounce_count' => 17,
                        'unique_id' => '95feb7fe7e06e6c11ca8d0c48cb46e87',
                        'html_email' => true,
                        'disabled' => true,
                        'subscribed_lists' => [
                            [
                                'id' => 2,
                                'name' => 'More news',
                                'description' => '',
                                'created_at' => '2016-06-22T15:01:17+00:00',
                                'public' => true,
                                'subscription_date' => '2016-08-22T15:01:17+00:00',
                            ],
                            [
                                'id' => 1,
                                'name' => 'News',
                                'description' => 'News (and some fun stuff)',
                                'created_at' => '2016-06-22T15:01:17+00:00',
                                'public' => true,
                                'subscription_date' => '2016-09-22T15:01:17+00:00',
                            ],
                        ],
                    ],
                ],
                'pagination' => [
                    'total' => 3,
                    'limit' => 25,
                    'has_more' => false,
                    'next_cursor' => 2,
                ],
            ]
        );
    }

    public function testCreateListWithValidPayloadReturns201(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class]);

        $payload = json_encode([
            'name' => 'New List',
            'description' => 'This is a new subscriber list.',
            'listPosition' => 3,
            'public' => true,
        ]);

        $this->authenticatedJsonRequest('POST', '/api/v2/lists', [], [], [], $payload);

        $this->assertHttpCreated();
        $response = $this->getDecodedJsonResponseContent();

        self::assertSame('New List', $response['name']);
    }

    public function testCreateListWithMissingNameReturnsValidationError(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class]);

        $payload = [
            'description' => 'Missing name field'
        ];

        $this->authenticatedJsonRequest('POST', '/api/v2/lists', [], [], [], json_encode($payload));
        $this->assertHttpUnprocessableEntity();
    }

    public function testCreateListWithoutSessionKeyReturnsForbidden(): void
    {
        self::getClient()->request('POST', '/api/v2/lists', [], [], [], json_encode([ 'name' => 'UnauthorizedList']));

        $this->assertHttpForbidden();
    }
}
