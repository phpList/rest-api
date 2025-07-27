<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Identity\Controller;

use PhpList\RestBundle\Identity\Controller\PasswordResetController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorFixture;

class PasswordResetControllerTest extends AbstractTestController
{
    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(
            PasswordResetController::class,
            self::getContainer()->get(PasswordResetController::class)
        );
    }

    public function testRequestPasswordResetWithNoJsonReturnsError400(): void
    {
        $this->jsonRequest('post', '/api/v2/password-reset/request');

        $this->assertHttpBadRequest();
        $data = $this->getDecodedJsonResponseContent();
        $this->assertStringContainsString('Invalid JSON:', $data['message']);
    }

    public function testRequestPasswordResetWithInvalidEmailReturnsError422(): void
    {
        $jsonData = json_encode(['email' => 'not-an-email']);
        $this->jsonRequest('post', '/api/v2/password-reset/request', [], [], [], $jsonData);

        $this->assertHttpUnprocessableEntity();
        $data = $this->getDecodedJsonResponseContent();
        $this->assertStringContainsString('This value is not a valid email address', $data['message']);
    }

    public function testRequestPasswordResetWithNonExistentEmailReturnsError404(): void
    {
        $this->loadFixtures([AdministratorFixture::class]);
        $jsonData = json_encode(['email' => 'nonexistent@example.com']);
        $this->jsonRequest('post', '/api/v2/password-reset/request', [], [], [], $jsonData);

        $this->assertHttpNotFound();
    }

    public function testRequestPasswordResetWithValidEmailReturnsSuccess(): void
    {
        $this->loadFixtures([AdministratorFixture::class]);
        $jsonData = json_encode(['email' => 'john@example.com']);
        $this->jsonRequest('post', '/api/v2/password-reset/request', [], [], [], $jsonData);

        $this->assertHttpNoContent();
    }

    public function testValidateTokenWithNoJsonReturnsError400(): void
    {
        $this->jsonRequest('post', '/api/v2/password-reset/validate');

        $this->assertHttpBadRequest();
        $data = $this->getDecodedJsonResponseContent();
        $this->assertStringContainsString('Invalid JSON:', $data['message']);
    }

    public function testValidateTokenWithInvalidTokenReturnsInvalidResult(): void
    {
        $this->loadFixtures([AdministratorFixture::class]);
        $jsonData = json_encode(['token' => 'invalid-token']);
        $this->jsonRequest('post', '/api/v2/password-reset/validate', [], [], [], $jsonData);

        $this->assertHttpOkay();
        $data = $this->getDecodedJsonResponseContent();
        $this->assertFalse($data['valid']);
    }

    public function testResetPasswordWithNoJsonReturnsError400(): void
    {
        $this->jsonRequest('post', '/api/v2/password-reset/reset');

        $this->assertHttpBadRequest();
        $data = $this->getDecodedJsonResponseContent();
        $this->assertStringContainsString('Invalid JSON:', $data['message']);
    }

    public function testResetPasswordWithInvalidTokenReturnsBadRequest(): void
    {
        $this->loadFixtures([AdministratorFixture::class]);
        $jsonData = json_encode(['token' => 'invalid-token', 'newPassword' => 'newPassword123']);
        $this->jsonRequest('post', '/api/v2/password-reset/reset', [], [], [], $jsonData);

        $this->assertHttpBadRequest();
        $data = $this->getDecodedJsonResponseContent();
        $this->assertEquals('Invalid or expired token', $data['message']);
    }

    public function testResetPasswordWithShortPasswordReturnsError422(): void
    {
        $this->loadFixtures([AdministratorFixture::class]);
        $jsonData = json_encode(['token' => 'valid-token', 'newPassword' => 'short']);
        $this->jsonRequest('post', '/api/v2/password-reset/reset', [], [], [], $jsonData);

        $this->assertHttpUnprocessableEntity();
        $data = $this->getDecodedJsonResponseContent();
        $this->assertStringContainsString('This value is too short', $data['message']);
    }
}
