<?php
declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Controller;

use Doctrine\Common\Persistence\ObjectRepository;
use PhpList\PhpList4\Core\Bootstrap;
use PhpList\PhpList4\Domain\Model\Identity\AdministratorToken;
use PhpList\PhpList4\Domain\Repository\Identity\AdministratorTokenRepository;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

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

        Bootstrap::getInstance()->setApplicationContext(Bootstrap::APPLICATION_CONTEXT_TESTING)->configure();

        $this->client = self::createClient(['environment' => Bootstrap::APPLICATION_CONTEXT_TESTING]);

        $this->administratorTokenRepository = $this->entityManager->getRepository(AdministratorToken::class);
    }

    /**
     * @test
     */
    public function getSessionsIsNotAllowed()
    {
        $this->expectException(MethodNotAllowedHttpException::class);

        $this->client->request('get', '/api/v2/sessions');
    }

    /**
     * @test
     */
    public function postSessionsWithNoJsonReturnsError400()
    {
        $this->client->request('post', '/api/v2/sessions');

        $responseContent = $this->client->getResponse()->getContent();
        $parsedResponseContent = json_decode($responseContent, true);

        self::assertSame(400, $this->client->getResponse()->getStatusCode());
        self::assertSame(
            [
                'code' => 1500559729794,
                'message' => 'No data',
                'description' => 'The request does not contain any data.',
            ],
            $parsedResponseContent
        );
    }

    /**
     * @test
     */
    public function postSessionsWithInvalidJsonReturnsError400()
    {
        $this->client->request('post', '/api/v2/sessions', [], [], [], 'Here be dragons, but no JSON.');

        $responseContent = $this->client->getResponse()->getContent();
        $parsedResponseContent = json_decode($responseContent, true);

        self::assertSame(400, $this->client->getResponse()->getStatusCode());
        self::assertSame(
            [
                'code' => 1500562402438,
                'message' => 'Invalid JSON data',
                'description' => 'The data in the request is invalid JSON.',
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
        $this->client->request('post', '/api/v2/sessions', [], [], [], $jsonData);

        $responseContent = $this->client->getResponse()->getContent();
        $parsedResponseContent = json_decode($responseContent, true);

        self::assertSame(400, $this->client->getResponse()->getStatusCode());
        self::assertSame(
            [
                'code' => 1500562647846,
                'message' => 'Incomplete credentials',
                'description' => 'The request does not contain both loginName and password.',
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

        $this->client->request('post', '/api/v2/sessions', [], [], [], json_encode($jsonData));
        $responseContent = $this->client->getResponse()->getContent();

        $parsedResponseContent = json_decode($responseContent, true);

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
        self::assertSame(
            [
                'code' => 1500567098798,
                'message' => 'Not authorized',
                'description' => 'The user name and password did not match any existing user.',
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

        $this->client->request('post', '/api/v2/sessions', [], [], [], json_encode($jsonData));
        $responseContent = $this->client->getResponse()->getContent();

        self::assertSame(201, $this->client->getResponse()->getStatusCode());
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

        $this->client->request('post', '/api/v2/sessions', [], [], [], json_encode($jsonData));
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
