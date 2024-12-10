<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Controller;

use DateTime;
use PhpList\Core\Domain\Model\Identity\AdministratorToken;
use PhpList\Core\Domain\Repository\Identity\AdministratorTokenRepository;
use PhpList\RestBundle\Controller\SessionController;
use PhpList\RestBundle\Tests\Integration\Controller\Fixtures\AdministratorFixture;
use PhpList\RestBundle\Tests\Integration\Controller\Fixtures\AdministratorTokenFixture;

/**
 * Testcase.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
class SessionControllerTest extends AbstractTestController
{
    private ?AdministratorTokenRepository $administratorTokenRepository = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->administratorTokenRepository = self::getContainer()->get(AdministratorTokenRepository::class);
    }

    public function testControllerIsAvailableViaContainer()
    {
        self::assertInstanceOf(
            SessionController::class,
            self::getClient()->getContainer()->get(SessionController::class)
        );
    }

    public function testGetSessionsIsNotAllowed()
    {
        self::getClient()->request('get', '/api/v2/sessions');

        $this->assertHttpMethodNotAllowed();
    }

    public function testPostSessionsWithNoJsonReturnsError400()
    {
        $this->jsonRequest('post', '/api/v2/sessions');

        $this->assertHttpBadRequest();
        $this->assertJsonResponseContentEquals(
            [
                'message' => 'Empty JSON data',
            ]
        );
    }

    public function testPostSessionsWithInvalidJsonWithJsonContentTypeReturnsError400()
    {
        $this->jsonRequest('post', '/api/v2/sessions', [], [], [], 'Here be dragons, but no JSON.');

        $this->assertHttpBadRequest();
        $this->assertJsonResponseContentEquals(
            [
                'message' => 'Could not decode request body.',
            ]
        );
    }

    public function testPostSessionsWithValidEmptyJsonWithOtherTypeReturnsError400()
    {
        self::getClient()->request('post', '/api/v2/sessions', [], [], ['CONTENT_TYPE' => 'application/xml'], '[]');

        $this->assertHttpBadRequest();
        $this->assertJsonResponseContentEquals(
            [
                'message' => 'Incomplete credentials',
            ]
        );
    }

    /**
     * @return string[][]
     */
    public static function incompleteCredentialsDataProvider(): array
    {
        return [
            'neither login_name nor password' => ['{}'],
            'login_name, but no password' => ['{"login_name": "larry@example.com"}'],
            'password, but no login_name' => ['{"password": "t67809oibuzfq2qg3"}'],
        ];
    }

    /**
     * @dataProvider incompleteCredentialsDataProvider
     */
    public function testPostSessionsWithValidIncompleteJsonReturnsError400(string $jsonData)
    {
        $this->jsonRequest('post', '/api/v2/sessions', [], [], [], $jsonData);

        $this->assertHttpBadRequest();
        $this->assertJsonResponseContentEquals(
            [
                'message' => 'Incomplete credentials',
            ]
        );
    }

    public function testPostSessionsWithInvalidCredentialsReturnsNotAuthorized()
    {
        $this->loadFixtures([AdministratorFixture::class]);

        $loginName = 'john.doe';
        $password = 'a sandwich and a cup of coffee';
        $jsonData = ['login_name' => $loginName, 'password' => $password];

        $this->jsonRequest('post', '/api/v2/sessions', [], [], [], json_encode($jsonData));

        $this->assertHttpUnauthorized();
        $this->assertJsonResponseContentEquals(
            [
                'message' => 'Not authorized',
            ]
        );
    }

    public function testPostSessionsActionWithValidCredentialsReturnsCreatedHttpStatus()
    {
        $this->loadFixtures([AdministratorFixture::class]);

        $loginName = 'john.doe';
        $password = 'Bazinga!';
        $jsonData = ['login_name' => $loginName, 'password' => $password];

        $this->jsonRequest('post', '/api/v2/sessions', [], [], [], json_encode($jsonData));

        $this->assertHttpCreated();
    }

    public function testPostSessionsActionWithValidCredentialsCreatesToken()
    {
        $administratorId = 1;
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class]);
        $jsonData = ['login_name' => 'john.doe', 'password' => 'Bazinga!'];

        $this->jsonRequest('post', '/api/v2/sessions', [], [], [], json_encode($jsonData));

        $responseContent = $this->getDecodedJsonResponseContent();
        $tokenId = $responseContent['id'];
        $key = $responseContent['key'];
        $expiry = $responseContent['expiry_date'];

        /** @var AdministratorToken $token */
        $token = $this->administratorTokenRepository->find($tokenId);

        self::assertNotNull($token);
        self::assertSame($key, $token->getKey());
        self::assertSame($expiry, $token->getExpiry()->format(DateTime::ATOM));
        self::assertSame($administratorId, $token->getAdministrator()->getId());
    }

    public function testDeleteSessionWithoutSessionKeyForExistingSessionReturnsForbiddenStatus()
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class]);

        self::getClient()->request('delete', '/api/v2/sessions/1');

        $this->assertHttpForbidden();
    }

    public function testDeleteSessionWithCurrentSessionKeyForExistingSessionReturnsNoContentStatus()
    {
        $this->authenticatedJsonRequest('delete', '/api/v2/sessions/1');

        $this->assertHttpNoContent();
    }

    public function testDeleteSessionWithCurrentSessionKeyForInexistentSessionReturnsNotFoundStatus()
    {
        $this->authenticatedJsonRequest('delete', '/api/v2/sessions/999');

        $this->assertHttpNotFound();
    }

    public function testDeleteSessionWithCurrentSessionAndOwnSessionKeyDeletesSession()
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class]);
        $this->authenticatedJsonRequest('delete', '/api/v2/sessions/1');

        self::assertNull($this->administratorTokenRepository->find(1));
    }

    public function testDeleteSessionWithCurrentSessionAndOwnSessionKeyKeepsReturnsForbiddenStatus()
    {
        $this->authenticatedJsonRequest('delete', '/api/v2/sessions/3');

        $this->assertHttpForbidden();
    }

    public function testDeleteSessionWithCurrentSessionAndOwnSessionKeyKeepsSession()
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class]);
        $this->authenticatedJsonRequest('delete', '/api/v2/sessions/3');

        self::assertNotNull($this->administratorTokenRepository->find(3));
    }
}
