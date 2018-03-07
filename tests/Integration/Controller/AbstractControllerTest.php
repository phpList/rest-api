<?php
declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Controller;

use PhpList\Core\TestingSupport\AbstractWebTest;
use PhpList\Core\TestingSupport\Traits\DatabaseTestTrait;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

/**
 * This is the base class for all REST controller integration tests.
 *
 * Make sure to call parent::setUp() first thing in your setUp method.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
abstract class AbstractControllerTest extends AbstractWebTest
{
    use DatabaseTestTrait;

    /**
     * @var string
     */
    const ADMINISTRATOR_TABLE_NAME = 'phplist_admin';

    /**
     * @var string
     */
    const TOKEN_TABLE_NAME = 'phplist_admintoken';

    protected function setUp()
    {
        $this->setUpDatabaseTest();
        $this->setUpWebTest();
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
        $this->getDataSet()->addTable(static::ADMINISTRATOR_TABLE_NAME, __DIR__ . '/Fixtures/Administrator.csv');
        $this->getDataSet()->addTable(static::TOKEN_TABLE_NAME, __DIR__ . '/Fixtures/AdministratorToken.csv');
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
        static::assertSame($expected, $this->getDecodedJsonResponseContent());
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

        static::assertSame($status, $response->getStatusCode());
        static::assertContains('application/json', (string)$response->headers);
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
     * Asserts that the current client response has a HTTP CREATED status (and the application/json content type).
     *
     * @return void
     */
    protected function assertHttpCreated()
    {
        $this->assertHttpStatusWithJsonContentType(Response::HTTP_CREATED);
    }

    /**
     * Asserts that the current client response has a HTTP NO CONTENT status.
     *
     * @return void
     */
    protected function assertHttpNoContent()
    {
        $response = $this->client->getResponse();

        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    /**
     * Asserts that the current client response has a HTTP BAD REQUEST status (and the application/json content type).
     *
     * @return void
     */
    protected function assertHttpBadRequest()
    {
        $this->assertHttpStatusWithJsonContentType(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Asserts that the current client response has a HTTP UNAUTHORIZED status (and the application/json content type).
     *
     * @return void
     */
    protected function assertHttpUnauthorized()
    {
        $this->assertHttpStatusWithJsonContentType(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Asserts that the current client response has a HTTP NOT FOUND status (and the application/json content type).
     *
     * @return void
     */
    protected function assertHttpNotFound()
    {
        $this->assertHttpStatusWithJsonContentType(Response::HTTP_NOT_FOUND);
    }

    /**
     * Asserts that the current client response has a HTTP FORBIDDEN status.
     *
     * @return void
     */
    protected function assertHttpForbidden()
    {
        $this->assertHttpStatusWithJsonContentType(Response::HTTP_FORBIDDEN);
    }

    /**
     * Asserts that the current client response has a HTTP METHOD NOT ALLOWED status.
     *
     * @return void
     */
    protected function assertHttpMethodNotAllowed()
    {
        $response = $this->client->getResponse();

        static::assertSame(Response::HTTP_METHOD_NOT_ALLOWED, $response->getStatusCode());
    }

    /**
     * Asserts that the current client response has a HTTP CONFLICT status and the corresponding error message
     * provided in the JSON response.
     *
     * @return void
     */
    protected function assertHttpConflict()
    {
        $this->assertHttpStatusWithJsonContentType(Response::HTTP_CONFLICT);

        static::assertSame(
            [
                'code' => Response::HTTP_CONFLICT,
                'message' => 'This resource already exists.',
            ],
            $this->getDecodedJsonResponseContent()
        );
    }

    /**
     * Asserts that the current client response has a HTTP UNPROCESSABLE ENTITY status.
     *
     * @return void
     */
    protected function assertHttpUnprocessableEntity()
    {
        $this->assertHttpStatusWithJsonContentType(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
