<?php
declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Controller;

use Doctrine\Common\Persistence\ObjectRepository;
use PhpList\Core\Domain\Model\Identity\AdministratorToken;
use PhpList\Core\Domain\Repository\Identity\AdministratorTokenRepository;
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
        $this->setUpDatabaseTest();
        $this->setUpWebTest();

        $this->administratorTokenRepository = $this->bootstrap->getContainer()
            ->get(AdministratorTokenRepository::class);
    }

    /**
     * @test
     */
    public function controllerIsAvailableViaContainer()
    {
        static::assertInstanceOf(
            SessionController::class,
            $this->client->getContainer()->get(SessionController::class)
        );
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
            'neither login_name nor password' => ['{}'],
            'login_name, but no password' => ['{"login_name": "larry@example.com"}'],
            'password, but no login_name' => ['{"password": "t67809oibuzfq2qg3"}'],
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
        $this->getDataSet()->addTable(static::ADMINISTRATOR_TABLE_NAME, __DIR__ . '/Fixtures/Administrator.csv');
        $this->applyDatabaseChanges();

        $loginName = 'john.doe';
        $password = 'a sandwich and a cup of coffee';
        $jsonData = ['login_name' => $loginName, 'password' => $password];

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
        $this->getDataSet()->addTable(static::ADMINISTRATOR_TABLE_NAME, __DIR__ . '/Fixtures/Administrator.csv');
        $this->applyDatabaseChanges();

        $loginName = 'john.doe';
        $password = 'Bazinga!';
        $jsonData = ['login_name' => $loginName, 'password' => $password];

        $this->jsonRequest('post', '/api/v2/sessions', [], [], [], json_encode($jsonData));

        $this->assertHttpCreated();
    }

    /**
     * @test
     */
    public function postSessionsActionWithValidCredentialsCreatesToken()
    {
        $administratorId = 1;
        $this->getDataSet()->addTable(static::ADMINISTRATOR_TABLE_NAME, __DIR__ . '/Fixtures/Administrator.csv');
        $this->applyDatabaseChanges();

        $loginName = 'john.doe';
        $password = 'Bazinga!';
        $jsonData = ['login_name' => $loginName, 'password' => $password];

        $this->jsonRequest('post', '/api/v2/sessions', [], [], [], json_encode($jsonData));

        $responseContent = $this->getDecodedJsonResponseContent();
        $tokenId = $responseContent['id'];
        $key = $responseContent['key'];
        $expiry = $responseContent['expiry'];

        /** @var AdministratorToken $token */
        $token = $this->administratorTokenRepository->find($tokenId);
        static::assertNotNull($token);
        static::assertSame($key, $token->getKey());
        static::assertSame($expiry, $token->getExpiry()->format(\DateTime::ATOM));
        static::assertSame($administratorId, $token->getAdministrator()->getId());
    }

    /**
     * @test
     */
    public function deleteSessionWithoutSessionKeyForExistingSessionReturnsForbiddenStatus()
    {
        $this->getDataSet()->addTable(static::ADMINISTRATOR_TABLE_NAME, __DIR__ . '/Fixtures/Administrator.csv');
        $this->getDataSet()->addTable(static::TOKEN_TABLE_NAME, __DIR__ . '/Fixtures/AdministratorToken.csv');
        $this->applyDatabaseChanges();

        $this->client->request('delete', '/api/v2/sessions/1');

        $this->assertHttpForbidden();
    }

    /**
     * @test
     */
    public function deleteSessionWithCurrentSessionKeyForExistingSessionReturnsNoContentStatus()
    {
        $this->authenticatedJsonRequest('delete', '/api/v2/sessions/1');

        $this->assertHttpNoContent();
    }

    /**
     * @test
     */
    public function deleteSessionWithCurrentSessionKeyForInexistentSessionReturnsNotFoundStatus()
    {
        $this->authenticatedJsonRequest('delete', '/api/v2/sessions/999');

        $this->assertHttpNotFound();
    }

    /**
     * @test
     */
    public function deleteSessionWithCurrentSessionAndOwnSessionKeyDeletesSession()
    {
        $this->authenticatedJsonRequest('delete', '/api/v2/sessions/1');

        static::assertNull($this->administratorTokenRepository->find(1));
    }

    /**
     * @test
     */
    public function deleteSessionWithCurrentSessionAndOwnSessionKeyKeepsReturnsForbiddenStatus()
    {
        $this->authenticatedJsonRequest('delete', '/api/v2/sessions/3');

        $this->assertHttpForbidden();
    }

    /**
     * @test
     */
    public function deleteSessionWithCurrentSessionAndOwnSessionKeyKeepsSession()
    {
        $this->authenticatedJsonRequest('delete', '/api/v2/sessions/3');

        static::assertNotNull($this->administratorTokenRepository->find(3));
    }
}
