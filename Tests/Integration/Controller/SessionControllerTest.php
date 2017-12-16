<?php
declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Controller;

use Doctrine\Common\Persistence\ObjectRepository;
use PhpList\PhpList4\Domain\Model\Identity\AdministratorToken;
use PhpList\PhpList4\Domain\Repository\Identity\AdministratorTokenRepository;
use PhpList\RestBundle\Controller\SessionController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Testcase.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
class SessionControllerTest extends AbstractControllerTest
{
    /**
     * @var AdministratorTokenRepository|ObjectRepository
     */
    private $administratorTokenRepository = null;

    protected function setUp()
    {
        parent::setUp();

        $this->administratorTokenRepository = $this->bootstrap->getContainer()
            ->get(AdministratorTokenRepository::class);
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
    public function getSessionsIsNotAllowed()
    {
        $this->client->request('get', '/api/v2/sessions');

        $this->assertHttpMethodNotAllowed();
    }

    /**
     * @test
     */
    public function postSessionsWithNoJsonReturnsError400()
    {
        $this->jsonRequest('post', '/api/v2/sessions');

        $this->assertHttpBadRequest();
        $this->assertJsonResponseContentEquals(
            [
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => 'Empty JSON data',
            ]
        );
    }

    /**
     * @test
     */
    public function postSessionsWithInvalidJsonWithJsonContentTypeReturnsError400()
    {
        $this->jsonRequest('post', '/api/v2/sessions', [], [], [], 'Here be dragons, but no JSON.');

        $this->assertHttpBadRequest();
        $this->assertJsonResponseContentEquals(
            [
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => 'Invalid json message received'
            ]
        );
    }

    /**
     * @test
     */
    public function postSessionsWithValidEmptyJsonWithOtherTypeReturnsError400()
    {
        $this->client->request('post', '/api/v2/sessions', [], [], ['CONTENT_TYPE' => 'application/xml'], '[]');

        $this->assertHttpBadRequest();
        $this->assertJsonResponseContentEquals(
            [
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => 'Invalid xml message received'
            ]
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
        $this->jsonRequest('post', '/api/v2/sessions', [], [], [], $jsonData);

        $this->assertHttpBadRequest();
        $this->assertJsonResponseContentEquals(
            [
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => 'Incomplete credentials',
            ]
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

        $this->jsonRequest('post', '/api/v2/sessions', [], [], [], json_encode($jsonData));

        $this->assertHttpUnauthorized();
        $this->assertJsonResponseContentEquals(
            [
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'Not authorized',
            ]
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

        $this->jsonRequest('post', '/api/v2/sessions', [], [], [], json_encode($jsonData));

        $this->assertHttpCreated();
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

        $this->jsonRequest('post', '/api/v2/sessions', [], [], [], json_encode($jsonData));

        $responseContent = $this->getDecodedJsonResponseContent();
        $tokenId = $responseContent['id'];
        $key = $responseContent['key'];
        $expiry = $responseContent['expiry'];

        /** @var AdministratorToken $token */
        $token = $this->administratorTokenRepository->find($tokenId);
        self::assertNotNull($token);
        self::assertSame($key, $token->getKey());
        self::assertSame($expiry, $token->getExpiry()->format(\DateTime::ATOM));
        self::assertSame($administratorId, $token->getAdministrator()->getId());
    }
}
