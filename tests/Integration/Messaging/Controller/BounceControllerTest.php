<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Messaging\Controller;

use DateTime;
use PhpList\Core\Domain\Messaging\Model\Bounce;
use PhpList\RestBundle\Messaging\Controller\BounceController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorFixture;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorTokenFixture;

class BounceControllerTest extends AbstractTestController
{
    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(BounceController::class, self::getContainer()->get(BounceController::class));
    }

    public function testListWithoutSessionKeyReturnsForbidden(): void
    {
        self::getClient()->request('GET', '/api/v2/bounces');
        $this->assertHttpForbidden();
    }

    public function testListWithExpiredSessionKeyReturnsForbidden(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class]);

        self::getClient()->request(
            'GET',
            '/api/v2/bounces',
            [],
            [],
            ['PHP_AUTH_USER' => 'unused', 'PHP_AUTH_PW' => 'expiredtoken']
        );

        $this->assertHttpForbidden();
    }

    public function testListWithValidSessionKeyReturnsOkayWithPaginationStructure(): void
    {
        $this->authenticatedJsonRequest('GET', '/api/v2/bounces');
        $this->assertHttpOkay();

        $response = $this->getDecodedJsonResponseContent();
        self::assertIsArray($response);
        self::assertArrayHasKey('items', $response);
        self::assertArrayHasKey('pagination', $response);
        self::assertIsArray($response['items']);
        self::assertIsArray($response['pagination']);
    }

    public function testListReturnsCreatedBounceData(): void
    {
        $bounce = new Bounce(
            date: new DateTime('2026-02-01T11:30:00+00:00'),
            header: 'Header',
            data: 'Data',
            status: 'not processed',
            comment: 'Test bounce'
        );
        $this->entityManager->persist($bounce);
        $this->entityManager->flush();

        $this->authenticatedJsonRequest('GET', '/api/v2/bounces');
        $this->assertHttpOkay();

        $response = $this->getDecodedJsonResponseContent();
        self::assertNotEmpty($response['items']);

        $firstItem = $response['items'][0];
        self::assertSame($bounce->getId(), $firstItem['id']);
        self::assertSame('not processed', $firstItem['status']);
        self::assertSame('Test bounce', $firstItem['comment']);
        self::assertSame('2026-02-01T11:30:00+00:00', $firstItem['date']);
        self::assertNull($firstItem['message_id']);
        self::assertNull($firstItem['subscriber_email']);
    }

    public function testDeleteWithoutSessionKeyReturnsForbidden(): void
    {
        self::getClient()->request('DELETE', '/api/v2/bounces/1');
        $this->assertHttpForbidden();
    }

    public function testDeleteWithInvalidIdReturnsNotFound(): void
    {
        $this->authenticatedJsonRequest('DELETE', '/api/v2/bounces/999999');
        $this->assertHttpNotFound();
    }

    public function testDeleteWithValidIdReturnsNoContentAndRemovesBounce(): void
    {
        $bounce = new Bounce(
            date: new DateTime('2026-02-01T11:30:00+00:00'),
            header: 'Header',
            data: 'Data',
            status: 'processed',
            comment: 'To delete'
        );
        $this->entityManager->persist($bounce);
        $this->entityManager->flush();
        $bounceId = $bounce->getId();
        self::assertNotNull($bounceId);

        $this->authenticatedJsonRequest('DELETE', '/api/v2/bounces/' . $bounceId);
        $this->assertHttpNoContent();

        $this->entityManager->clear();
        self::assertNull($this->entityManager->getRepository(Bounce::class)->find($bounceId));
    }
}
