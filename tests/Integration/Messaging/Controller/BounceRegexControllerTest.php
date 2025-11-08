<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Messaging\Controller;

use PhpList\RestBundle\Messaging\Controller\BounceRegexController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorFixture;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorTokenFixture;

class BounceRegexControllerTest extends AbstractTestController
{
    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(BounceRegexController::class, self::getContainer()->get(BounceRegexController::class));
    }

    public function testListWithoutSessionKeyReturnsForbidden(): void
    {
        self::getClient()->request('GET', '/api/v2/bounces/regex');
        $this->assertHttpForbidden();
    }

    public function testListWithExpiredSessionKeyReturnsForbidden(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class]);

        self::getClient()->request(
            'GET',
            '/api/v2/bounces/regex',
            [],
            [],
            ['PHP_AUTH_USER' => 'unused', 'PHP_AUTH_PW' => 'expiredtoken']
        );

        $this->assertHttpForbidden();
    }

    public function testListWithValidSessionKeyReturnsOkay(): void
    {
        $this->authenticatedJsonRequest('GET', '/api/v2/bounces/regex');
        $this->assertHttpOkay();
    }

    public function testCreateGetDeleteFlow(): void
    {
        $payload = json_encode([
            'regex' => '/mailbox is full/i',
            'action' => 'delete',
            'list_order' => 0,
            'admin' => 1,
            'comment' => 'Auto-generated rule',
            'status' => 'active',
        ]);

        $this->authenticatedJsonRequest('POST', '/api/v2/bounces/regex', [], [], [], $payload);
        $this->assertHttpCreated();
        $created = $this->getDecodedJsonResponseContent();
        $this->assertSame('/mailbox is full/i', $created['regex']);
        $this->assertSame(md5('/mailbox is full/i'), $created['regex_hash']);

        $hash = $created['regex_hash'];
        $this->authenticatedJsonRequest('GET', '/api/v2/bounces/regex/' . $hash);
        $this->assertHttpOkay();
        $one = $this->getDecodedJsonResponseContent();
        $this->assertSame($hash, $one['regex_hash']);

        $this->authenticatedJsonRequest('GET', '/api/v2/bounces/regex');
        $this->assertHttpOkay();
        $list = $this->getDecodedJsonResponseContent();
        $this->assertIsArray($list);
        $this->assertIsArray($list[0] ?? []);

        $this->authenticatedJsonRequest('DELETE', '/api/v2/bounces/regex/' . $hash);
        $this->assertHttpNoContent();

        $this->authenticatedJsonRequest('GET', '/api/v2/bounces/regex/' . $hash);
        $this->assertHttpNotFound();
    }
}
