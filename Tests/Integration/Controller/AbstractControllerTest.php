<?php
declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Controller;

use Doctrine\ORM\EntityManagerInterface;
use PhpList\PhpList4\Core\Bootstrap;
use PhpList\PhpList4\Core\Environment;
use PHPUnit\DbUnit\Database\Connection;
use PHPUnit\DbUnit\DataSet\CsvDataSet;
use PHPUnit\DbUnit\TestCaseTrait;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

/**
 * This is the base class for all REST controller integration tests.
 *
 * Make sure to call parent::setUp() first thing in your setUp method.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
abstract class AbstractControllerTest extends WebTestCase
{
    use TestCaseTrait;

    /**
     * @var string
     */
    const ADMINISTRATOR_TABLE_NAME = 'phplist_admin';

    /**
     * @var string
     */
    const TOKEN_TABLE_NAME = 'phplist_admintoken';

    /**
     * @var Connection
     */
    private $databaseConnection = null;

    /**
     * @var \PDO
     */
    private static $pdo = null;

    /**
     * @var CsvDataSet
     */
    private $dataSet = null;

    /**
     * @var Bootstrap
     */
    protected $bootstrap = null;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager = null;

    /**
     * @var Client
     */
    protected $client = null;

    protected function setUp()
    {
        $this->initializeDatabaseTester();
        $this->bootstrap = Bootstrap::getInstance()->setEnvironment(Environment::TESTING)->configure();
        $this->entityManager = $this->bootstrap->getEntityManager();
        self::assertTrue($this->entityManager->isOpen());

        $this->client = self::createClient(['environment' => Environment::TESTING]);
    }

    /**
     * Initializes the CSV data set and the database tester.
     *
     * @return void
     */
    protected function initializeDatabaseTester()
    {
        $this->dataSet = new CsvDataSet();

        $this->databaseTester = null;
        $this->getDatabaseTester()->setSetUpOperation($this->getSetUpOperation());
    }

    protected function tearDown()
    {
        $this->entityManager->close();

        $this->getDatabaseTester()->setTearDownOperation($this->getTearDownOperation());
        $this->getDatabaseTester()->setDataSet($this->getDataSet());
        $this->getDatabaseTester()->onTearDown();

        // Destroy the tester after the test is run to keep DB connections
        // from piling up.
        $this->databaseTester = null;

        Bootstrap::purgeInstance();
    }

    /**
     * Returns the test database connection.
     *
     * @return Connection
     */
    protected function getConnection(): Connection
    {
        if ($this->databaseConnection === null) {
            if (self::$pdo === null) {
                self::$pdo = new \PDO(
                    'mysql:dbname=' . getenv('PHPLIST_DATABASE_NAME'),
                    getenv('PHPLIST_DATABASE_USER'),
                    getenv('PHPLIST_DATABASE_PASSWORD')
                );
            }
            $this->databaseConnection = $this->createDefaultDBConnection(self::$pdo);
        }

        return $this->databaseConnection;
    }

    /**
     * Returns the test data set.
     *
     * Add data to in the individual test by calling $this->getDataSet()->addTable.
     *
     * @return CsvDataSet
     */
    protected function getDataSet(): CsvDataSet
    {
        return $this->dataSet;
    }

    /**
     * Applies all database changes on $this->dataSet.
     *
     * This methods needs to be called after the last addTable call in each test.
     *
     * @return void
     */
    protected function applyDatabaseChanges()
    {
        $this->getDatabaseTester()->setDataSet($this->getDataSet());
        $this->getDatabaseTester()->onSetUp();
    }

    /**
     * Calls a URI with the application/json content type.
     *
     * @param string $method The request method
     * @param string $uri The URI to fetch
     * @param array $parameters The Request parameters
     * @param array $files The files
     * @param array $server The server parameters (HTTP headers are referenced with a HTTP_ prefix as PHP does)
     * @param string|null $content The raw body data
     *
     * @return Crawler
     */
    protected function jsonRequest(
        string $method,
        string $uri,
        array $parameters = [],
        array $files = [],
        array $server = [],
        string $content = null
    ): Crawler {
        $serverWithContentType = $server;
        $serverWithContentType['CONTENT_TYPE'] = 'application/json';

        return $this->client->request($method, $uri, $parameters, $files, $serverWithContentType, $content);
    }

    /**
     * Calls a URI with a valid, unexpired authentication token for a superuser.
     *
     * @param string $method
     * @param string $uri
     * @param array $parameters
     * @param array $files
     * @param array $server
     * @param string|null $content
     *
     * @return Crawler
     */
    protected function authenticatedJsonRequest(
        string $method,
        string $uri,
        array $parameters = [],
        array $files = [],
        array $server = [],
        string $content = null
    ): Crawler {
        $this->getDataSet()->addTable(self::ADMINISTRATOR_TABLE_NAME, __DIR__ . '/Fixtures/Administrator.csv');
        $this->getDataSet()->addTable(self::TOKEN_TABLE_NAME, __DIR__ . '/Fixtures/AdministratorToken.csv');
        $this->applyDatabaseChanges();

        $serverWithAuthentication = $server;
        $serverWithAuthentication['PHP_AUTH_USER'] = 'unused';
        $serverWithAuthentication['PHP_AUTH_PW'] = 'cfdf64eecbbf336628b0f3071adba762';

        return $this->jsonRequest($method, $uri, $parameters, $files, $serverWithAuthentication, $content);
    }

    /**
     * Returns the decoded JSON response content.
     *
     * @return array
     */
    protected function getDecodedJsonResponseContent(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }

    /**
     * Asserts that the (decoded) JSON response content is the same as the expected array.
     *
     * @param array $expected
     *
     * @return void
     */
    protected function assertJsonResponseContentEquals(array $expected)
    {
        self::assertSame($expected, $this->getDecodedJsonResponseContent());
    }

    /**
     * Asserts that the current client response has the given HTTP status and the application/json content type.
     *
     * @param int $status
     *
     * @return void
     */
    protected function assertHttpStatusWithJsonContentType(int $status)
    {
        $response = $this->client->getResponse();

        self::assertSame($status, $response->getStatusCode());
        self::assertContains('application/json', (string)$response->headers);
    }

    /**
     * Asserts that the current client response has a HTTP OKAY status (and the application/json content type).
     *
     * @return void
     */
    protected function assertHttpOkay()
    {
        $this->assertHttpStatusWithJsonContentType(Response::HTTP_OK);
    }

    /**
     * Asserts that the current client response has a HTTP CREATED status  (and the application/json content type).
     *
     * @return void
     */
    protected function assertHttpCreated()
    {
        $this->assertHttpStatusWithJsonContentType(Response::HTTP_CREATED);
    }

    /**
     * Asserts that the current client response has a HTTP BAD REQUEST status  (and the application/json content type).
     *
     * @return void
     */
    protected function assertHttpBadRequest()
    {
        $this->assertHttpStatusWithJsonContentType(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Asserts that the current client response has a HTTP UNAUTHORIZED status  (and the application/json content type).
     *
     * @return void
     */
    protected function assertHttpUnauthorized()
    {
        $this->assertHttpStatusWithJsonContentType(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Asserts that the current client response has a HTTP FORBIDDEN status and the corresponding error message
     * provided in the JSON response.
     *
     * @return void
     */
    protected function assertHttpForbidden()
    {
        $this->assertHttpStatusWithJsonContentType(Response::HTTP_FORBIDDEN);

        self::assertSame(
            [
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'No valid session key was provided as basic auth password.',
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }

    /**
     * Asserts that the current client response has a HTTP METHOD NOT ALLOWED status.
     *
     * @return void
     */
    protected function assertHttpMethodNotAllowed()
    {
        $response = $this->client->getResponse();

        self::assertSame(Response::HTTP_METHOD_NOT_ALLOWED, $response->getStatusCode());
    }
}
