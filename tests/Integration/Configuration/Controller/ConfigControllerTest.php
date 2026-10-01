<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Configuration\Controller;

use PhpList\Core\Domain\Configuration\Model\Config;
use PhpList\RestBundle\Configuration\Controller\ConfigController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorFixture;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorTokenFixture;

class ConfigControllerTest extends AbstractTestController
{
    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(ConfigController::class, self::getContainer()->get(ConfigController::class));
    }

    public function testListWithoutSessionKeyReturnsUnauthorized(): void
    {
        self::getClient()->request('GET', '/api/v2/configs');
        $this->assertHttpUnauthorized();
    }

    public function testListWithExpiredSessionKeyReturnsUnauthorized(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class]);

        self::getClient()->request(
            'GET',
            '/api/v2/configs',
            [],
            [],
            ['PHP_AUTH_USER' => 'unused', 'PHP_AUTH_PW' => 'expiredtoken']
        );

        $this->assertHttpUnauthorized();
    }

    public function testListWithValidSessionKeyReturnsOkayWithPaginationStructure(): void
    {
        $this->authenticatedJsonRequest('GET', '/api/v2/configs');
        $this->assertHttpOkay();

        $response = $this->getDecodedJsonResponseContent();
        self::assertArrayHasKey('items', $response);
        self::assertArrayHasKey('pagination', $response);
    }

    public function testListReturnsCreatedConfigData(): void
    {
        $config = new Config();
        $config->setKey('organisation_name');
        $config->setValue('Example Organisation');
        $config->setEditable(true);
        $config->setType('text');

        $this->entityManager->persist($config);
        $this->entityManager->flush();

        $this->authenticatedJsonRequest('GET', '/api/v2/configs');
        $this->assertHttpOkay();

        $response = $this->getDecodedJsonResponseContent();
        self::assertNotEmpty($response['items']);

        $found = false;
        foreach ($response['items'] as $item) {
            if ($item['key'] === 'organisation_name') {
                $found = true;
                self::assertSame('Example Organisation', $item['value']);
                self::assertTrue($item['editable']);
                self::assertSame('text', $item['type']);
            }
        }

        self::assertTrue($found, 'Created config item not found in list response.');
    }

    public function testGetOneWithoutSessionKeyReturnsUnauthorized(): void
    {
        self::getClient()->request('GET', '/api/v2/configs/organisation_name');
        $this->assertHttpUnauthorized();
    }

    public function testGetOneWithValidSessionReturnsOkayAndData(): void
    {
        $config = new Config();
        $config->setKey('site_title');
        $config->setValue('My Site');
        $config->setEditable(true);

        $this->entityManager->persist($config);
        $this->entityManager->flush();

        $this->authenticatedJsonRequest('GET', '/api/v2/configs/site_title');
        $this->assertHttpOkay();

        $response = $this->getDecodedJsonResponseContent();
        self::assertSame('site_title', $response['key']);
        self::assertSame('My Site', $response['value']);
        self::assertTrue($response['editable']);
    }

    public function testGetOneWithInvalidKeyReturnsNotFound(): void
    {
        $this->authenticatedJsonRequest('GET', '/api/v2/configs/nonexistent_key');
        $this->assertHttpNotFound();
    }

    public function testCreateWithoutSessionKeyReturnsUnauthorized(): void
    {
        $json = json_encode(['key' => 'new_key', 'value' => 'val']);
        $this->jsonRequest('POST', '/api/v2/configs', [], [], [], $json);
        $this->assertHttpUnauthorized();
    }

    public function testCreateWithValidSessionCreatesConfig(): void
    {
        $json = json_encode(['key' => 'new_key', 'value' => 'val', 'editable' => true, 'type' => 'text']);
        $this->authenticatedJsonRequest('POST', '/api/v2/configs', [], [], [], $json);

        $this->assertHttpCreated();
        $response = $this->getDecodedJsonResponseContent();
        self::assertSame('new_key', $response['key']);
        self::assertSame('val', $response['value']);
        self::assertTrue($response['editable']);
        self::assertSame('text', $response['type']);
    }

    public function testCreateWithDuplicateKeyReturnsConflict(): void
    {
        $config = new Config();
        $config->setKey('dup_key');
        $config->setValue('first');
        $this->entityManager->persist($config);
        $this->entityManager->flush();

        $json = json_encode(['key' => 'dup_key', 'value' => 'second']);
        $this->authenticatedJsonRequest('POST', '/api/v2/configs', [], [], [], $json);

        $this->assertHttpConflict();
    }

    public function testUpdateWithoutSessionKeyReturnsUnauthorized(): void
    {
        $this->jsonRequest('PUT', '/api/v2/configs/some_key', [], [], [], json_encode(['value' => 'x']));
        $this->assertHttpUnauthorized();
    }

    public function testUpdateWithValidSessionUpdatesValue(): void
    {
        $config = new Config();
        $config->setKey('up_key');
        $config->setValue('old');
        $config->setEditable(true);
        $this->entityManager->persist($config);
        $this->entityManager->flush();

        $this->authenticatedJsonRequest('PUT', '/api/v2/configs/up_key', [], [], [], json_encode(['value' => 'new']));

        $this->assertHttpOkay();
        $response = $this->getDecodedJsonResponseContent();
        self::assertSame('new', $response['value']);
    }

    public function testUpdateForNonexistentKeyReturnsNotFound(): void
    {
        $this->authenticatedJsonRequest(
            'PUT',
            '/api/v2/configs/does_not_exist',
            [],
            [],
            [],
            json_encode(['value' => 'x'])
        );
        $this->assertHttpNotFound();
    }

    public function testUpdateNonEditableReturnsForbidden(): void
    {
        $config = new Config();
        $config->setKey('locked_key');
        $config->setValue('orig');
        $config->setEditable(false);
        $this->entityManager->persist($config);
        $this->entityManager->flush();

        $this->authenticatedJsonRequest(
            'PUT',
            '/api/v2/configs/locked_key',
            [],
            [],
            [],
            json_encode(['value' => 'changed'])
        );

        $this->assertHttpForbidden();
    }

    public function testDeleteWithoutSessionKeyReturnsUnauthorized(): void
    {
        self::getClient()->request('DELETE', '/api/v2/configs/some_key');
        $this->assertHttpUnauthorized();
    }

    public function testDeleteWithValidSessionDeletesConfig(): void
    {
        $config = new Config();
        $config->setKey('del_key');
        $config->setValue('to delete');
        $this->entityManager->persist($config);
        $this->entityManager->flush();

        $this->authenticatedJsonRequest('DELETE', '/api/v2/configs/del_key');
        $this->assertHttpNoContent();

        $this->entityManager->clear();
        self::assertNull($this->entityManager->getRepository(Config::class)->find('del_key'));
    }

    public function testDeleteNonexistentReturnsNotFound(): void
    {
        $this->authenticatedJsonRequest('DELETE', '/api/v2/configs/unknown_key');
        $this->assertHttpNotFound();
    }
}
