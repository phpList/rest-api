<?php
declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Controller;

use Doctrine\Common\Persistence\ObjectRepository;
use PhpList\PhpList4\Core\Environment;
use PhpList\PhpList4\Domain\Model\Identity\AdministratorToken;
use PhpList\PhpList4\Domain\Repository\Identity\AdministratorTokenRepository;
use PhpList\RestBundle\Controller\SessionController;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpFoundation\Response;

/**
 * Testcase.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
class SessionControllerTest extends AbstractControllerTest
{
    /**
     * @var string
     */
    const ADMINISTRATOR_TABLE_NAME = 'phplist_admin';

    /**
     * @var Client
     */
    private $client = null;

    /**
     * @var AdministratorTokenRepository|ObjectRepository
     */
    private $administratorTokenRepository = null;

    protected function setUp()
    {
        parent::setUp();

        $this->administratorTokenRepository = $this->bootstrap->getContainer()
            ->get(AdministratorTokenRepository::class);

        $this->client = self::createClient(['environment' => Environment::TESTING]);
    }

    /**
     * @test
     */
    public function controllerIsAvailableViaContainer()
    {
        self::assertInstanceOf(SessionController::class, $this->client->getContainer()->get(SessionController::class));
    }

    /**
     * @test
     */
    public function rootUrlHasHtmlContentType()
    {
        $this->client->request('get', '/');

        $response = $this->client->getResponse();

        self::assertContains('text/html', (string)$response->headers);
    }

    /**
     * @test
     */
    public function getSessionsIsNotAllowed()
    {
        $this->client->request('get', '/api/v2/sessions');

        $response = $this->client->getResponse();
        self::assertSame(Response::HTTP_METHOD_NOT_ALLOWED, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function postSessionsWithNoJsonReturnsError400()
    {
        $this->client->request('post', '/api/v2/sessions', [], [], ['CONTENT_TYPE' => 'application/json']);

        $response = $this->client->getResponse();
        $parsedResponseContent = json_decode($response->getContent(), true);

        self::assertContains('application/json', (string)$response->headers);
        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(
            [
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => 'Empty JSON data',
            ],
            $parsedResponseContent
        );
    }

    /**
     * @test
     */
    public function postSessionsWithInvalidJsonWithJsonContentTypeReturnsError400()
    {
        $this->client->request(
            'post',
            '/api/v2/sessions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'Here be dragons, but no JSON.'
        );

        $response = $this->client->getResponse();
        $parsedResponseContent = json_decode($response->getContent(), true);

        self::assertContains('application/json', (string)$response->headers);
        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(
            [
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => 'Invalid json message received'
            ],
            $parsedResponseContent
        );
    }

    /**
     * @test
     */
    public function postSessionsWithValidEmptyJsonWithOtherTypeReturnsError400()
    {
        $this->client->request('post', '/api/v2/sessions', [], [], ['CONTENT_TYPE' => 'application/xml'], '[]');

        $response = $this->client->getResponse();
        $parsedResponseContent = json_decode($response->getContent(), true);

        self::assertContains('application/json', (string)$response->headers);
        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(
            [
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => 'Invalid xml message received'
            ],
            $parsedResponseContent
        );
    }

    /**
     * @return string[][]
     */
    public function incompleteCredentialsDataProvider(): array
    {
        return [
            'neither loginName nor password' => ['{}'],
            'loginName, but no password' => ['{"loginName": "larry@example.com"}'],
            'password, but no loginName' => ['{"password": "t67809oibuzfq2qg3"}'],
        ];
    }

    /**
     * @test
     * @param string $jsonData
     * @dataProvider incompleteCredentialsDataProvider
     */
    public function postSessionsWithValidIncompleteJsonReturnsError400(string $jsonData)
    {
        $this->client->request('post', '/api/v2/sessions', [], [], ['CONTENT_TYPE' => 'application/json'], $jsonData);

        $response = $this->client->getResponse();
        $parsedResponseContent = json_decode($response->getContent(), true);

        self::assertContains('application/json', (string)$response->headers);
        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(
            [
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => 'Incomplete credentials',
            ],
            $parsedResponseContent
        );
    }

    /**
     * @test
     */
    public function postSessionsWithInvalidCredentialsReturnsNotAuthorized()
    {
        $this->getDataSet()->addTable(self::ADMINISTRATOR_TABLE_NAME, __DIR__ . '/Fixtures/Administrator.csv');
        $this->applyDatabaseChanges();

        $loginName = 'john.doe';
        $password = 'a sandwich and a cup of coffee';
        $jsonData = ['loginName' => $loginName, 'password' => $password];

        $this->client->request(
            'post',
            '/api/v2/sessions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($jsonData)
        );
        $response = $this->client->getResponse();
        $parsedResponseContent = json_decode($response->getContent(), true);

        self::assertContains('application/json', (string)$response->headers);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(
            [
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'Not authorized',
            ],
            $parsedResponseContent
        );
    }

    /**
     * @test
     */
    public function postSessionsActionWithValidCredentialsReturnsCreatedHttpStatus()
    {
        $this->getDataSet()->addTable(self::ADMINISTRATOR_TABLE_NAME, __DIR__ . '/Fixtures/Administrator.csv');
        $this->applyDatabaseChanges();

        $loginName = 'john.doe';
        $password = 'Bazinga!';
        $jsonData = ['loginName' => $loginName, 'password' => $password];

        $this->client->request(
            'post',
            '/api/v2/sessions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($jsonData)
        );
        $response = $this->client->getResponse();

        self::assertContains('application/json', (string)$response->headers);
        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function postSessionsActionWithValidCredentialsCreatesToken()
    {
        $administratorId = 1;
        $this->getDataSet()->addTable(self::ADMINISTRATOR_TABLE_NAME, __DIR__ . '/Fixtures/Administrator.csv');
        $this->applyDatabaseChanges();

        $loginName = 'john.doe';
        $password = 'Bazinga!';
        $jsonData = ['loginName' => $loginName, 'password' => $password];

        $this->client->request(
            'post',
            '/api/v2/sessions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($jsonData)
        );
        $responseContent = $this->client->getResponse()->getContent();

        $parsedResponseContent = json_decode($responseContent, true);
        $tokenId = $parsedResponseContent['id'];
        $key = $parsedResponseContent['key'];
        $expiry = $parsedResponseContent['expiry'];

        /** @var AdministratorToken $token */
        $token = $this->administratorTokenRepository->find($tokenId);
        self::assertNotNull($token);
        self::assertSame($key, $token->getKey());
        self::assertSame($expiry, $token->getExpiry()->format(\DateTime::ATOM));
        self::assertSame($administratorId, $token->getAdministrator()->getId());
    }
}
