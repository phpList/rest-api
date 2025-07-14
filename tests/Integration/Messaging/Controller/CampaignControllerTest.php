<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Messaging\Controller;

use PhpList\RestBundle\Messaging\Controller\CampaignController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorFixture;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorTokenFixture;
use PhpList\RestBundle\Tests\Integration\Messaging\Fixtures\MessageFixture;

class CampaignControllerTest extends AbstractTestController
{
    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(CampaignController::class, self::getContainer()->get(CampaignController::class));
    }

    public function testGetCampaignsWithoutSessionKeyReturnsForbidden(): void
    {
        self::getClient()->request('GET', '/api/v2/campaigns');
        $this->assertHttpForbidden();
    }

    public function testGetCampaignsWithExpiredSessionKeyReturnsForbidden(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class]);

        self::getClient()->request(
            'GET',
            '/api/v2/campaigns',
            [],
            [],
            ['PHP_AUTH_USER' => 'unused', 'PHP_AUTH_PW' => 'expiredtoken']
        );

        $this->assertHttpForbidden();
    }

    public function testGetCampaignsWithValidSessionReturnsOkay(): void
    {
        $this->authenticatedJsonRequest('GET', '/api/v2/campaigns');
        $this->assertHttpOkay();
    }

    public function testGetCampaignsReturnsCampaignData(): void
    {
        $this->loadFixtures([AdministratorFixture::class, MessageFixture::class]);

        $this->authenticatedJsonRequest('GET', '/api/v2/campaigns');
        $response = $this->getDecodedJsonResponseContent();

        self::assertIsArray($response);
        self::assertArrayHasKey('id', $response['items'][0]);
        self::assertArrayHasKey('message_content', $response['items'][0]);
    }

    public function testGetSingleCampaignWithValidSessionReturnsData(): void
    {
        $this->loadFixtures([MessageFixture::class]);

        $this->authenticatedJsonRequest('GET', '/api/v2/campaigns/1');
        $this->assertHttpOkay();

        $response = $this->getDecodedJsonResponseContent();
        self::assertSame(1, $response['id']);
    }

    public function testGetSingleCampaignWithoutSessionReturnsForbidden(): void
    {
        $this->loadFixtures([MessageFixture::class]);
        self::getClient()->request('GET', '/api/v2/campaigns/1');
        $this->assertHttpForbidden();
    }

    public function testGetCampaignWithInvalidIdReturnsNotFound(): void
    {
        $this->authenticatedJsonRequest('GET', '/api/v2/campaigns/999');
        $this->assertHttpNotFound();
    }

    public function testDeleteCampaignReturnsNoContent(): void
    {
        $this->loadFixtures([AdministratorFixture::class, MessageFixture::class]);

        $this->authenticatedJsonRequest('DELETE', '/api/v2/campaigns/1');
        $this->assertHttpNoContent();
    }
    public function testSendMessageWithoutSessionReturnsForbidden(): void
    {
        $this->loadFixtures([MessageFixture::class]);
        self::getClient()->request('POST', '/api/v2/campaigns/1/send');
        $this->assertHttpForbidden();
    }

    public function testSendMessageWithValidSessionReturnsOkay(): void
    {
        $this->loadFixtures([AdministratorFixture::class, MessageFixture::class]);

        $this->authenticatedJsonRequest('POST', '/api/v2/campaigns/1/send');
        $this->assertHttpOkay();

        $response = $this->getDecodedJsonResponseContent();
        self::assertSame(1, $response['id']);
    }

    public function testSendMessageWithInvalidIdReturnsNotFound(): void
    {
        $this->authenticatedJsonRequest('POST', '/api/v2/campaigns/999/send');
        $this->assertHttpNotFound();
    }
}
