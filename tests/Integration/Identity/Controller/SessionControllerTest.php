<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Identity\Controller;

use DateTime;
use PhpList\Core\Domain\Identity\Model\AdministratorToken;
use PhpList\Core\Domain\Identity\Repository\AdministratorTokenRepository;
use PhpList\RestBundle\Identity\Controller\SessionController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorFixture;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorTokenFixture;

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
            self::getContainer()->get(SessionController::class)
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
        $data = $this->getDecodedJsonResponseContent();
        $this->assertStringContainsString('Invalid JSON:', $data['message']);
    }

    public function testPostSessionsWithInvalidJsonWithJsonContentTypeReturnsError400()
    {
        $this->jsonRequest('post', '/api/v2/sessions', [], [], [], 'Here be dragons, but no JSON.');

        $this->assertHttpBadRequest();
        $data = $this->getDecodedJsonResponseContent();
        $this->assertStringContainsString('Invalid JSON:', $data['message']);
    }

    public function testPostSessionsWithValidEmptyJsonWithOtherTypeReturnsError422()
    {
        self::getClient()->request('post', '/api/v2/sessions', [], [], ['CONTENT_TYPE' => 'application/xml'], '[]');

        $this->assertHttpUnprocessableEntity();
        $this->assertJsonResponseContentEquals(
            [
                'message' => "loginName: This value should not be blank.\npassword: This value should not be blank.",
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
            'login_name, but no password' => ['{"loginName": "larry@example.com"}'],
            'password, but no login_name' => ['{"password": "t67809oibuzfq2qg3"}'],
        ];
    }

    /**
     * @dataProvider incompleteCredentialsDataProvider
     */
    public function testPostSessionsWithValidIncompleteJsonReturnsError400(string $jsonData)
    {
        $this->jsonRequest('post', '/api/v2/sessions', [], [], [], $jsonData);

        $this->assertHttpUnprocessableEntity();
        $data = $this->getDecodedJsonResponseContent();
        $this->assertStringContainsString('This value should not be blank', $data['message']);
    }

    public function testPostSessionsWithInvalidCredentialsReturnsNotAuthorized()
    {
        $this->loadFixtures([AdministratorFixture::class]);

        $loginName = 'john.doe.1';
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
        $jsonData = ['loginName' => $loginName, 'password' => $password];

        $this->jsonRequest('post', '/api/v2/sessions', [], [], [], json_encode($jsonData));

        $this->assertHttpCreated();
    }

    public function testPostSessionsActionWithValidCredentialsCreatesToken()
    {
        $administratorId = 1;
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class]);
        $jsonData = ['loginName' => 'john.doe', 'password' => 'Bazinga!'];

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

    public function testPostSessionWithExtraFieldsIsIgnored(): void
    {
        $this->loadFixtures([AdministratorFixture::class]);

        $jsonData = json_encode([
            'loginName' => 'john.doe',
            'password' => 'Bazinga!',
            'extraField' => 'ignore_me'
        ]);

        $this->jsonRequest('post', '/api/v2/sessions', [], [], [], $jsonData);

        $this->assertHttpCreated();
        $response = $this->getDecodedJsonResponseContent();
        self::assertArrayNotHasKey('extraField', $response);
    }

    public function testDeleteSessionWithInvalidFormatIdReturns404(): void
    {
        $this->authenticatedJsonRequest('DELETE', '/api/v2/sessions/not-an-id');
        $this->assertHttpNotFound();
    }

    public function testPostSessionWithWrongHttpMethodReturns405(): void
    {
        self::getClient()->request('PUT', '/api/v2/sessions');
        $this->assertHttpMethodNotAllowed();
    }

    public function testDeleteSessionWithNoSuchSessionReturns404(): void
    {
        $this->authenticatedJsonRequest('DELETE', '/api/v2/sessions/999999');
        $this->assertHttpNotFound();
    }
}
