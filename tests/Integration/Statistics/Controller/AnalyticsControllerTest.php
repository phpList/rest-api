<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Statistics\Controller;

use PhpList\RestBundle\Statistics\Controller\AnalyticsController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorFixture;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorTokenFixture;
use PhpList\RestBundle\Tests\Integration\Messaging\Fixtures\MessageFixture;
use PhpList\RestBundle\Tests\Integration\Subscription\Fixtures\SubscriberFixture;

class AnalyticsControllerTest extends AbstractTestController
{
    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(AnalyticsController::class, self::getContainer()->get(AnalyticsController::class));
    }

    public function testGetCampaignStatisticsWithoutSessionKeyReturnsForbidden(): void
    {
        self::getClient()->request('GET', '/api/v2/analytics/campaigns');
        $this->assertHttpForbidden();
    }

    public function testGetCampaignStatisticsWithExpiredSessionKeyReturnsForbidden(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class]);

        self::getClient()->request(
            'GET',
            '/api/v2/analytics/campaigns',
            [],
            [],
            ['PHP_AUTH_USER' => 'unused', 'PHP_AUTH_PW' => 'expiredtoken']
        );

        $this->assertHttpForbidden();
    }

    public function testGetCampaignStatisticsWithValidSessionReturnsOkay(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class, MessageFixture::class]);
        
        $this->authenticatedJsonRequest('GET', '/api/v2/analytics/campaigns');
        $this->assertHttpOkay();
    }

    public function testGetCampaignStatisticsReturnsCampaignData(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class, MessageFixture::class]);

        $this->authenticatedJsonRequest('GET', '/api/v2/analytics/campaigns');
        $response = $this->getDecodedJsonResponseContent();

        self::assertIsArray($response);
        self::assertArrayHasKey('campaigns', $response);
        self::assertArrayHasKey('total', $response);
        self::assertArrayHasKey('hasMore', $response);
        self::assertArrayHasKey('lastId', $response);
    }

    public function testGetViewOpensStatisticsWithoutSessionKeyReturnsForbidden(): void
    {
        self::getClient()->request('GET', '/api/v2/analytics/view-opens');
        $this->assertHttpForbidden();
    }

    public function testGetViewOpensStatisticsWithValidSessionReturnsOkay(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class, MessageFixture::class]);
        
        $this->authenticatedJsonRequest('GET', '/api/v2/analytics/view-opens');
        $this->assertHttpOkay();
    }

    public function testGetViewOpensStatisticsReturnsViewData(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class, MessageFixture::class]);

        $this->authenticatedJsonRequest('GET', '/api/v2/analytics/view-opens');
        $response = $this->getDecodedJsonResponseContent();

        self::assertIsArray($response);
        self::assertArrayHasKey('campaigns', $response);
        self::assertArrayHasKey('total', $response);
        self::assertArrayHasKey('hasMore', $response);
        self::assertArrayHasKey('lastId', $response);
    }

    public function testGetTopDomainsWithoutSessionKeyReturnsForbidden(): void
    {
        self::getClient()->request('GET', '/api/v2/analytics/domains/top');
        $this->assertHttpForbidden();
    }

    public function testGetTopDomainsWithValidSessionReturnsOkay(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class, SubscriberFixture::class]);
        
        $this->authenticatedJsonRequest('GET', '/api/v2/analytics/domains/top');
        $this->assertHttpOkay();
    }

    public function testGetTopDomainsReturnsDomainsData(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class, SubscriberFixture::class]);

        $this->authenticatedJsonRequest('GET', '/api/v2/analytics/domains/top');
        $response = $this->getDecodedJsonResponseContent();

        self::assertIsArray($response);
        self::assertArrayHasKey('domains', $response);
        self::assertArrayHasKey('total', $response);
    }

    public function testGetDomainConfirmationStatisticsWithoutSessionKeyReturnsForbidden(): void
    {
        self::getClient()->request('GET', '/api/v2/analytics/domains/confirmation');
        $this->assertHttpForbidden();
    }

    public function testGetDomainConfirmationStatisticsWithValidSessionReturnsOkay(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class, SubscriberFixture::class]);
        
        $this->authenticatedJsonRequest('GET', '/api/v2/analytics/domains/confirmation');
        $this->assertHttpOkay();
    }

    public function testGetDomainConfirmationStatisticsReturnsConfirmationData(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class, SubscriberFixture::class]);

        $this->authenticatedJsonRequest('GET', '/api/v2/analytics/domains/confirmation');
        $response = $this->getDecodedJsonResponseContent();

        self::assertIsArray($response);
        self::assertArrayHasKey('domains', $response);
        self::assertArrayHasKey('total', $response);
    }

    public function testGetTopLocalPartsWithoutSessionKeyReturnsForbidden(): void
    {
        self::getClient()->request('GET', '/api/v2/analytics/local-parts/top');
        $this->assertHttpForbidden();
    }

    public function testGetTopLocalPartsWithValidSessionReturnsOkay(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class, SubscriberFixture::class]);
        
        $this->authenticatedJsonRequest('GET', '/api/v2/analytics/local-parts/top');
        $this->assertHttpOkay();
    }

    public function testGetTopLocalPartsReturnsLocalPartsData(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class, SubscriberFixture::class]);

        $this->authenticatedJsonRequest('GET', '/api/v2/analytics/local-parts/top');
        $response = $this->getDecodedJsonResponseContent();

        self::assertIsArray($response);
        self::assertArrayHasKey('localParts', $response);
        self::assertArrayHasKey('total', $response);
    }
}
