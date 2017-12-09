<?php
declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Controller;

use PhpList\PhpList4\Core\Environment;
use PhpList\RestBundle\Controller\ListController;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpFoundation\Response;

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
    const ADMINISTRATOR_TABLE_NAME = 'phplist_admin';

    /**
     * @var string
     */
    const TOKEN_TABLE_NAME = 'phplist_admintoken';

    /**
     * @var string
     */
    const LISTS_TABLE_NAME = 'phplist_list';

    /**
     * @var Client
     */
    private $client = null;

    protected function setUp()
    {
        parent::setUp();

        $this->client = self::createClient(['environment' => Environment::TESTING]);
    }

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

        $response = $this->client->getResponse();
        $parsedResponseContent = json_decode($response->getContent(), true);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertContains('application/json', (string)$response->headers);
        self::assertSame(
            [
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'No valid session key was provided as basic auth password.',
            ],
            $parsedResponseContent
        );
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

        $response = $this->client->getResponse();
        $parsedResponseContent = json_decode($response->getContent(), true);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertContains('application/json', (string)$response->headers);
        self::assertSame(
            [
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'No valid session key was provided as basic auth password.',
            ],
            $parsedResponseContent
        );
    }

    /**
     * @test
     */
    public function getListsWithCurrentSessionKeyReturnsOkayStatus()
    {
        $this->getDataSet()->addTable(self::ADMINISTRATOR_TABLE_NAME, __DIR__ . '/Fixtures/Administrator.csv');
        $this->getDataSet()->addTable(self::TOKEN_TABLE_NAME, __DIR__ . '/Fixtures/AdministratorToken.csv');
        $this->applyDatabaseChanges();

        $this->client->request(
            'get',
            '/api/v2/lists',
            [],
            [],
            ['PHP_AUTH_USER' => 'unused', 'PHP_AUTH_PW' => 'cfdf64eecbbf336628b0f3071adba762']
        );

        $response = $this->client->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertContains('application/json', (string)$response->headers);
    }

    /**
     * @test
     */
    public function getListsWithCurrentSessionKeyReturnsListData()
    {
        $this->getDataSet()->addTable(self::ADMINISTRATOR_TABLE_NAME, __DIR__ . '/Fixtures/Administrator.csv');
        $this->getDataSet()->addTable(self::TOKEN_TABLE_NAME, __DIR__ . '/Fixtures/AdministratorToken.csv');
        $this->getDataSet()->addTable(self::LISTS_TABLE_NAME, __DIR__ . '/Fixtures/SubscriberList.csv');
        $this->applyDatabaseChanges();

        $this->client->request(
            'get',
            '/api/v2/lists',
            [],
            [],
            ['PHP_AUTH_USER' => 'unused', 'PHP_AUTH_PW' => 'cfdf64eecbbf336628b0f3071adba762']
        );

        $response = $this->client->getResponse();
        $parsedResponseContent = json_decode($response->getContent(), true);

        self::assertSame(
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
            ],
            $parsedResponseContent
        );
    }
}
