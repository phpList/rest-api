<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Identity\Controller;

use PhpList\Core\Domain\Identity\Repository\AdminAttributeDefinitionRepository;
use PhpList\RestBundle\Identity\Controller\AdminAttributeDefinitionController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdminAttributeDefinitionFixture;

class AdminAttributeDefinitionControllerTest extends AbstractTestController
{
    private ?AdminAttributeDefinitionRepository $definitionRepository = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->definitionRepository = self::getContainer()->get(AdminAttributeDefinitionRepository::class);
    }

    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(
            AdminAttributeDefinitionController::class,
            self::getContainer()->get(AdminAttributeDefinitionController::class)
        );
    }

    public function testCreateAttributeDefinitionWithValidDataReturnsCreated(): void
    {
        $this->authenticatedJsonRequest('post', '/api/v2/administrators/attributes', [], [], [], json_encode([
            'name' => 'Test Attribute',
            'type' => 'textline',
            'order' => 1,
            'defaultValue' => 'default',
            'required' => true,
        ]));

        $this->assertHttpCreated();
        $data = $this->getDecodedJsonResponseContent();
        self::assertSame('Test Attribute', $data['name']);
        self::assertSame('textline', $data['type']);
        self::assertSame(1, $data['list_order']);
        self::assertSame('default', $data['default_value']);
        self::assertTrue($data['required']);
    }

    public function testUpdateAttributeDefinitionReturnsOk(): void
    {
        $this->loadFixtures([AdminAttributeDefinitionFixture::class]);
        $id = 2;

        $this->authenticatedJsonRequest('put', '/api/v2/administrators/attributes/' . $id, [], [], [], json_encode([
            'name' => 'Updated Attribute',
            'type' => 'hidden',
            'required' => true,
        ]));

        $this->assertHttpOkay();
        $data = $this->getDecodedJsonResponseContent();
        self::assertSame('Updated Attribute', $data['name']);
        self::assertSame('hidden', $data['type']);
        self::assertTrue($data['required']);
    }

    public function testDeleteAttributeDefinitionReturnsNoContent(): void
    {
        $this->loadFixtures([AdminAttributeDefinitionFixture::class]);
        $id = 3;

        $this->authenticatedJsonRequest('delete', '/api/v2/administrators/attributes/' . $id);
        $this->assertHttpNoContent();

        self::assertNull($this->definitionRepository->find($id));
    }

    public function testGetPaginatedReturnsOk(): void
    {
        $this->loadFixtures([AdminAttributeDefinitionFixture::class]);

        $this->authenticatedJsonRequest('get', '/api/v2/administrators/attributes');
        $this->assertHttpOkay();
        $data = $this->getDecodedJsonResponseContent();
        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('pagination', $data);
        self::assertGreaterThanOrEqual(2, count($data['items']));
    }

    public function testGetAttributeDefinitionReturnsData(): void
    {
        $this->loadFixtures([AdminAttributeDefinitionFixture::class]);
        $id = 6;

        $this->authenticatedJsonRequest('get', '/api/v2/administrators/attributes/' . $id);
        $this->assertHttpOkay();
        $data = $this->getDecodedJsonResponseContent();
        self::assertSame('Test Get Attribute', $data['name']);
        self::assertSame('text', $data['type']);
    }

    public function testGetAttributeDefinitionNotFound(): void
    {
        $this->authenticatedJsonRequest('get', '/api/v2/administrators/attributes/999999');
        $this->assertHttpNotFound();
    }

    public function testCreateAttributeDefinitionWithInvalidJsonReturns400(): void
    {
        $this->authenticatedJsonRequest('post', '/api/v2/administrators/attributes', [], [], [], 'not json');
        $this->assertHttpBadRequest();
    }

    public function testCreateAttributeDefinitionWithMissingFieldsReturns422(): void
    {
        $this->authenticatedJsonRequest('post', '/api/v2/administrators/attributes', [], [], [], json_encode([]));
        $this->assertHttpUnprocessableEntity();
    }

    public function testUpdateAttributeDefinitionWithInvalidIdReturns404(): void
    {
        $this->authenticatedJsonRequest('put', '/api/v2/administrators/attributes/999999', [], [], [], json_encode([
            'name' => 'Updated Name'
        ]));
        $this->assertHttpNotFound();
    }
}
