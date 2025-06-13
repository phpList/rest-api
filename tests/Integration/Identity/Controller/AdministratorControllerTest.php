<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Identity\Controller;

use PhpList\Core\Domain\Identity\Repository\AdministratorRepository;
use PhpList\RestBundle\Identity\Controller\AdministratorController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorFixture;

class AdministratorControllerTest extends AbstractTestController
{
    private ?AdministratorRepository $administratorRepository = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->administratorRepository = self::getContainer()->get(AdministratorRepository::class);
    }

    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(
            AdministratorController::class,
            self::getContainer()->get(AdministratorController::class)
        );
    }

    public function testGetAdministratorsReturnsOk(): void
    {
        $this->loadFixtures([AdministratorFixture::class]);
        $this->authenticatedJsonRequest('get', '/api/v2/administrators');

        $this->assertHttpOkay();
        $data = $this->getDecodedJsonResponseContent();
        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('pagination', $data);
    }

    public function testGetAdministratorReturnsData(): void
    {
        $this->loadFixtures([AdministratorFixture::class]);
        $this->authenticatedJsonRequest('get', '/api/v2/administrators/1');

        $this->assertHttpOkay();
        $data = $this->getDecodedJsonResponseContent();
        self::assertSame('john.doe', $data['login_name']);
    }

    public function testGetAdministratorNotFound(): void
    {
        $this->authenticatedJsonRequest('get', '/api/v2/administrators/999');
        $this->assertHttpNotFound();
    }

    public function testCreateAdministratorWithValidDataReturnsCreated(): void
    {
        $this->authenticatedJsonRequest('post', '/api/v2/administrators', [], [], [], json_encode([
            'loginName' => 'new.admin',
            'password' => 'NewPassword123!',
            'email' => 'new.admin@example.com',
            'privileges' => [
                'subscribers' => true,
                'campaigns' => false,
                'statistics' => true,
                'settings' => false,
            ],
        ]));

        $this->assertHttpCreated();
        $data = $this->getDecodedJsonResponseContent();
        self::assertSame('new.admin', $data['login_name']);

        $administrator = $this->administratorRepository->findOneBy(['loginName' => 'new.admin']);
        $privileges = $administrator->getPrivileges()->all();
        self::assertTrue($privileges['subscribers']);
        self::assertFalse($privileges['campaigns']);
        self::assertTrue($privileges['statistics']);
        self::assertFalse($privileges['settings']);
    }

    public function testUpdateAdministratorReturnsOk(): void
    {
        $this->loadFixtures([AdministratorFixture::class]);

        $this->authenticatedJsonRequest('put', '/api/v2/administrators/1', [], [], [], json_encode([
            'email' => 'updated@example.com',
            'privileges' => [
                'subscribers' => false,
                'campaigns' => true,
                'statistics' => false,
                'settings' => true,
            ],
        ]));

        $this->assertHttpOkay();
        $data = $this->getDecodedJsonResponseContent();
        self::assertSame('updated@example.com', $data['email']);

        $administrator = $this->administratorRepository->find(1);
        $privileges = $administrator->getPrivileges()->all();
        self::assertFalse($privileges['subscribers']);
        self::assertTrue($privileges['campaigns']);
        self::assertFalse($privileges['statistics']);
        self::assertTrue($privileges['settings']);
    }

    public function testDeleteAdministratorReturnsNoContent(): void
    {
        $this->loadFixtures([AdministratorFixture::class]);

        $this->authenticatedJsonRequest('delete', '/api/v2/administrators/1');
        $this->assertHttpNoContent();

        self::assertNull($this->administratorRepository->find(1));
    }

    public function testDeleteAdministratorNotFound(): void
    {
        $this->authenticatedJsonRequest('delete', '/api/v2/administrators/99999');
        $this->assertHttpNotFound();
    }

    public function testCreateAdministratorWithInvalidJsonReturns400(): void
    {
        $this->authenticatedJsonRequest('post', '/api/v2/administrators', [], [], [], 'not json');
        $this->assertHttpBadRequest();
    }

    public function testCreateAdministratorWithMissingFieldsReturns422(): void
    {
        $this->authenticatedJsonRequest('post', '/api/v2/administrators', [], [], [], json_encode([]));
        $this->assertHttpUnprocessableEntity();
    }

    public function testPutAdministratorWithInvalidIdReturns404(): void
    {
        $this->authenticatedJsonRequest('put', '/api/v2/administrators/9999', [], [], [], json_encode([
            'email' => 'example@example.com'
        ]));

        $this->assertHttpNotFound();
    }

    public function testUpdateAdministratorPrivilegesOnly(): void
    {
        $this->loadFixtures([AdministratorFixture::class]);

        $originalAdmin = $this->administratorRepository->find(1);
        $originalEmail = $originalAdmin->getEmail();

        $this->authenticatedJsonRequest('put', '/api/v2/administrators/1', [], [], [], json_encode([
            'privileges' => [
                'subscribers' => true,
                'campaigns' => true,
                'statistics' => true,
                'settings' => true,
            ],
        ]));

        $this->assertHttpOkay();

        $updatedAdmin = $this->administratorRepository->find(1);
        self::assertSame($originalEmail, $updatedAdmin->getEmail());

        $privileges = $updatedAdmin->getPrivileges()->all();
        self::assertTrue($privileges['subscribers']);
        self::assertTrue($privileges['campaigns']);
        self::assertTrue($privileges['statistics']);
        self::assertTrue($privileges['settings']);
    }
}
