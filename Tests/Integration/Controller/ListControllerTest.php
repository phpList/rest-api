<?php
declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Controller;

use PhpList\RestBundle\Controller\ListController;

/**
 * Testcase.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
class ListControllerTest extends AbstractControllerTest
{
    /**
     * @var string
     */
    const LISTS_TABLE_NAME = 'phplist_list';

    /**
     * @var string
     */
    const SUBSCRIBER_TABLE_NAME = 'phplist_user_user';

    /**
     * @var string
     */
    const SUBSCRIPTION_TABLE_NAME = 'phplist_listuser';

    /**
     * @test
     */
    public function controllerIsAvailableViaContainer()
    {
        self::assertInstanceOf(ListController::class, $this->client->getContainer()->get(ListController::class));
    }

    /**
     * @test
     */
    public function getListsWithoutSessionKeyReturnsForbiddenStatus()
    {
        $this->client->request('get', '/api/v2/lists');

        $this->assertHttpForbidden();
    }

    /**
     * @test
     */
    public function getListsWithExpiredSessionKeyReturnsForbiddenStatus()
    {
        $this->getDataSet()->addTable(self::ADMINISTRATOR_TABLE_NAME, __DIR__ . '/Fixtures/Administrator.csv');
        $this->getDataSet()->addTable(self::TOKEN_TABLE_NAME, __DIR__ . '/Fixtures/AdministratorToken.csv');
        $this->applyDatabaseChanges();

        $this->client->request(
            'get',
            '/api/v2/lists',
            [],
            [],
            ['PHP_AUTH_USER' => 'unused', 'PHP_AUTH_PW' => 'cfdf64eecbbf336628b0f3071adba763']
        );

        $this->assertHttpForbidden();
    }

    /**
     * @test
     */
    public function getListsWithCurrentSessionKeyReturnsOkayStatus()
    {
        $this->authenticatedJsonRequest('get', '/api/v2/lists');

        $this->assertHttpOkay();
    }

    /**
     * @test
     */
    public function getListsWithCurrentSessionKeyReturnsListData()
    {
        $this->getDataSet()->addTable(self::LISTS_TABLE_NAME, __DIR__ . '/Fixtures/SubscriberList.csv');
        $this->applyDatabaseChanges();

        $this->authenticatedJsonRequest('get', '/api/v2/lists');

        $this->assertJsonResponseContentEquals(
            [
                [
                    'name' => 'News',
                    'description' => 'News (and some fun stuff)',
                    'creation_date' => '2016-06-22T15:01:17+00:00',
                    'list_position' => 12,
                    'subject_prefix' => 'phpList',
                    'public' => true,
                    'category' => 'news',
                    'id' => 1,
                ],
                [
                    'name' => 'More news',
                    'description' => '',
                    'creation_date' => '2016-06-22T15:01:17+00:00',
                    'list_position' => 12,
                    'subject_prefix' => '',
                    'public' => true,
                    'category' => '',
                    'id' => 2,
                ]
            ]
        );
    }

    /**
     * @test
     */
    public function getListMembersWithoutSessionKeyReturnsForbiddenStatus()
    {
        $this->client->request('get', '/api/v2/lists/1/members');

        $this->assertHttpForbidden();
    }

    /**
     * @test
     */
    public function getListMembersWithExpiredSessionKeyReturnsForbiddenStatus()
    {
        $this->getDataSet()->addTable(self::ADMINISTRATOR_TABLE_NAME, __DIR__ . '/Fixtures/Administrator.csv');
        $this->getDataSet()->addTable(self::TOKEN_TABLE_NAME, __DIR__ . '/Fixtures/AdministratorToken.csv');
        $this->applyDatabaseChanges();

        $this->client->request(
            'get',
            '/api/v2/lists/1/members',
            [],
            [],
            ['PHP_AUTH_USER' => 'unused', 'PHP_AUTH_PW' => 'cfdf64eecbbf336628b0f3071adba763']
        );

        $this->assertHttpForbidden();
    }

    /**
     * @test
     */
    public function getListMembersWithCurrentSessionKeyForInexistentListReturnsNotFoundStatus()
    {
        $this->authenticatedJsonRequest('get', '/api/v2/lists/999/members');

        $this->assertHttpNotFound();
    }

    /**
     * @test
     */
    public function getListMembersWithCurrentSessionKeyForExistingListReturnsOkayStatus()
    {
        $this->getDataSet()->addTable(self::LISTS_TABLE_NAME, __DIR__ . '/Fixtures/SubscriberList.csv');
        $this->applyDatabaseChanges();

        $this->authenticatedJsonRequest('get', '/api/v2/lists/1/members');

        $this->assertHttpOkay();
    }

    /**
     * @test
     */
    public function getListMembersWithCurrentSessionKeyForExistingListWithoutSubscribersReturnsEmptyArray()
    {
        $this->getDataSet()->addTable(self::LISTS_TABLE_NAME, __DIR__ . '/Fixtures/SubscriberList.csv');
        $this->applyDatabaseChanges();

        $this->authenticatedJsonRequest('get', '/api/v2/lists/1/members');

        $this->assertJsonResponseContentEquals([]);
    }

    /**
     * @test
     */
    public function getListMembersWithCurrentSessionKeyForExistingListWithSubscribersReturnsSubscribers()
    {
        $this->getDataSet()->addTable(self::LISTS_TABLE_NAME, __DIR__ . '/Fixtures/SubscriberList.csv');
        $this->getDataSet()->addTable(self::SUBSCRIBER_TABLE_NAME, __DIR__ . '/Fixtures/Subscriber.csv');
        $this->getDataSet()->addTable(self::SUBSCRIPTION_TABLE_NAME, __DIR__ . '/Fixtures/Subscription.csv');
        $this->applyDatabaseChanges();

        $this->authenticatedJsonRequest('get', '/api/v2/lists/2/members');

        $this->assertJsonResponseContentEquals(
            [
                [
                    'creation_date' => '2016-07-22T15:01:17+00:00',
                    'email' => 'oliver@example.com',
                    'confirmed' => true,
                    'blacklisted' => true,
                    'bounce_count' => 17,
                    'unique_id' => '95feb7fe7e06e6c11ca8d0c48cb46e89',
                    'html_email' => true,
                    'disabled' => true,
                    'id' => 1,
                ]
            ]
        );
    }
}
