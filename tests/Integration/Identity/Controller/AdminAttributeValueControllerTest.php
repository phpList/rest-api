<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Identity\Controller;

use PhpList\RestBundle\Identity\Controller\AdminAttributeValueController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorFixture;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdminAttributeDefinitionFixture;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdminAttributeValueFixture;

class AdminAttributeValueControllerTest extends AbstractTestController
{
    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(
            AdminAttributeValueController::class,
            self::getContainer()->get(AdminAttributeValueController::class)
        );
    }

    public function testCreateOrUpdateAttributeValueWithValidDataReturnsCreated(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdminAttributeDefinitionFixture::class]);
        $definitionId = 1;
        $adminId = 1;

        $this->authenticatedJsonRequest(
            'post',
            '/api/v2/administrators/' . $adminId . '/attributes/' . $definitionId,
            [],
            [],
            [],
            json_encode(['value' => 'test value'])
        );

        $this->assertHttpCreated();
        $data = $this->getDecodedJsonResponseContent();
        self::assertSame('test value', $data['value']);
        self::assertSame($definitionId, $data['definition']['id']);
        self::assertSame($adminId, $data['administrator']['id']);
    }

    public function testUpdateAttributeValueReturnsOk(): void
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            AdminAttributeDefinitionFixture::class,
            AdminAttributeValueFixture::class
        ]);
        $definitionId = 7;
        $adminId = 1;

        $this->authenticatedJsonRequest(
            'post',
            '/api/v2/administrators/' . $adminId . '/attributes/' . $definitionId,
            [],
            [],
            [],
            json_encode(['value' => 'updated value'])
        );

        $this->assertHttpCreated();
        $data = $this->getDecodedJsonResponseContent();
        self::assertSame('updated value', $data['value']);
    }

    public function testDeleteAttributeValueReturnsNoContent(): void
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            AdminAttributeDefinitionFixture::class,
            AdminAttributeValueFixture::class
        ]);
        $definitionId = 8;
        $adminId = 1;

        $this->authenticatedJsonRequest(
            'delete',
            '/api/v2/administrators/' . $adminId . '/attributes/' . $definitionId
        );
        $this->assertHttpNoContent();
    }

    public function testGetPaginatedReturnsOk(): void
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            AdminAttributeDefinitionFixture::class,
            AdminAttributeValueFixture::class
        ]);
        $adminId = 1;

        $this->authenticatedJsonRequest('get', '/api/v2/administrators/' . $adminId . '/attributes');
        $this->assertHttpOkay();
        $data = $this->getDecodedJsonResponseContent();
        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('pagination', $data);
        self::assertGreaterThanOrEqual(2, count($data['items']));
    }

    public function testGetAttributeValueReturnsData(): void
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            AdminAttributeDefinitionFixture::class,
            AdminAttributeValueFixture::class
        ]);
        $definitionId = 11;
        $adminId = 1;

        $this->authenticatedJsonRequest(
            'get',
            '/api/v2/administrators/' . $adminId . '/attributes/' . $definitionId
        );
        $this->assertHttpOkay();
        $data = $this->getDecodedJsonResponseContent();
        self::assertSame('test get value', $data['value']);
    }

    public function testGetAttributeValueNotFound(): void
    {
        $this->loadFixtures([AdministratorFixture::class]);
        $adminId = 1;

        $this->authenticatedJsonRequest(
            'get',
            '/api/v2/administrators/' . $adminId . '/attributes/999999'
        );
        $this->assertHttpNotFound();
    }

    public function testCreateAttributeValueWithInvalidJsonReturns400(): void
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            AdminAttributeDefinitionFixture::class
        ]);
        $definitionId = 12;
        $adminId = 1;

        $this->authenticatedJsonRequest(
            'post',
            '/api/v2/administrators/' . $adminId . '/attributes/' . $definitionId,
            [],
            [],
            [],
            'not json'
        );
        $this->assertHttpBadRequest();
    }

    public function testCreateAttributeValueWithInvalidDefinitionIdReturns404(): void
    {
        $this->loadFixtures([AdministratorFixture::class]);
        $adminId = 1;

        $this->authenticatedJsonRequest(
            'post',
            '/api/v2/administrators/' . $adminId . '/attributes/999999',
            [],
            [],
            [],
            json_encode(['value' => 'test value'])
        );
        $this->assertHttpNotFound();
    }

    public function testCreateAttributeValueWithInvalidAdminIdReturns404(): void
    {
        $this->loadFixtures([AdminAttributeDefinitionFixture::class]);
        $definitionId = 13;

        $this->authenticatedJsonRequest(
            'post',
            '/api/v2/administrators/999999/attributes/' . $definitionId,
            [],
            [],
            [],
            json_encode(['value' => 'test value'])
        );
        $this->assertHttpNotFound();
    }
}
