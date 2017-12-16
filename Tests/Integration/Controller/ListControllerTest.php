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
}
