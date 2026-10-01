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

    public function testGetCampaignStatisticsWithoutSessionKeyReturnsUnauthorized(): void
    {
        self::getClient()->request('GET', '/api/v2/analytics/campaigns');
        $this->assertHttpUnauthorized();
    }

    public function testGetCampaignStatisticsWithExpiredSessionKeyReturnsUnauthorized(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class]);

        self::getClient()->request(
            'GET',
            '/api/v2/analytics/campaigns',
            [],
            [],
            ['PHP_AUTH_USER' => 'unused', 'PHP_AUTH_PW' => 'expiredtoken']
        );

        $this->assertHttpUnauthorized();
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

        self::assertArrayHasKey('items', $response);
        self::assertArrayHasKey('pagination', $response);
    }

    public function testGetViewOpensStatisticsWithoutSessionKeyReturnsUnauthorized(): void
    {
        self::getClient()->request('GET', '/api/v2/analytics/view-opens');
        $this->assertHttpUnauthorized();
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

        self::assertArrayHasKey('items', $response);
        self::assertArrayHasKey('pagination', $response);
        self::assertIsArray($response['items']);
        self::assertIsArray($response['pagination']);
    }

    public function testGetTopDomainsWithoutSessionKeyReturnsUnauthorized(): void
    {
        self::getClient()->request('GET', '/api/v2/analytics/domains/top');
        $this->assertHttpUnauthorized();
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

        self::assertArrayHasKey('items', $response);
        self::assertArrayHasKey('total', $response);
        self::assertIsArray($response['items']);
        self::assertIsInt($response['total']);
    }

    public function testGetTopDomainsWithLimitParameter(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class, SubscriberFixture::class]);

        $this->authenticatedJsonRequest('GET', '/api/v2/analytics/domains/top?limit=5');
        $response = $this->getDecodedJsonResponseContent();

        self::assertArrayHasKey('items', $response);
        self::assertIsArray($response['items']);
        self::assertLessThanOrEqual(5, count($response['items']));
    }

    public function testGetTopDomainsWithMinSubscribersParameter(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class, SubscriberFixture::class]);

        $this->authenticatedJsonRequest('GET', '/api/v2/analytics/domains/top?min_subscribers=10');
        $response = $this->getDecodedJsonResponseContent();

        self::assertArrayHasKey('items', $response);
        self::assertIsArray($response['items']);

        // Verify all domains have at least 10 subscribers
        foreach ($response['items'] as $domain) {
            self::assertArrayHasKey('subscribers', $domain);
            self::assertGreaterThanOrEqual(10, $domain['subscribers']);
        }
    }

    public function testGetTopDomainsWithBothParameters(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class, SubscriberFixture::class]);

        $this->authenticatedJsonRequest('GET', '/api/v2/analytics/domains/top?limit=3&min_subscribers=10');
        $response = $this->getDecodedJsonResponseContent();

        self::assertArrayHasKey('items', $response);
        self::assertIsArray($response['items']);
        self::assertLessThanOrEqual(3, count($response['items']));

        foreach ($response['items'] as $domain) {
            self::assertArrayHasKey('subscribers', $domain);
            self::assertGreaterThanOrEqual(10, $domain['subscribers']);
        }
    }

    public function testGetTopDomainsWithInvalidLimitParameter(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class, SubscriberFixture::class]);

        $this->authenticatedJsonRequest('GET', '/api/v2/analytics/domains/top?limit=invalid');
        $response = $this->getDecodedJsonResponseContent();

        self::assertArrayHasKey('items', $response);
        self::assertIsArray($response['items']);
    }

    public function testGetDomainConfirmationStatisticsWithoutSessionKeyReturnsUnauthorized(): void
    {
        self::getClient()->request('GET', '/api/v2/analytics/domains/confirmation');
        $this->assertHttpUnauthorized();
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

        self::assertArrayHasKey('items', $response);
        self::assertArrayHasKey('total', $response);
    }

    public function testGetTopLocalPartsWithoutSessionKeyReturnsUnauthorized(): void
    {
        self::getClient()->request('GET', '/api/v2/analytics/local-parts/top');
        $this->assertHttpUnauthorized();
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

        self::assertArrayHasKey('items', $response);
        self::assertArrayHasKey('total', $response);
        self::assertIsArray($response['items']);
        self::assertIsInt($response['total']);
    }

    public function testGetTopLocalPartsWithLimitParameter(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class, SubscriberFixture::class]);

        $this->authenticatedJsonRequest('GET', '/api/v2/analytics/local-parts/top?limit=5');
        $response = $this->getDecodedJsonResponseContent();

        self::assertArrayHasKey('items', $response);
        self::assertIsArray($response['items']);
        self::assertLessThanOrEqual(5, count($response['items']));
    }

    public function testGetTopLocalPartsWithInvalidLimitParameter(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class, SubscriberFixture::class]);

        $this->authenticatedJsonRequest('GET', '/api/v2/analytics/local-parts/top?limit=invalid');
        $response = $this->getDecodedJsonResponseContent();

        self::assertArrayHasKey('items', $response);
        self::assertIsArray($response['items']);
    }

    public function testGetDashboardSummaryWithoutSessionKeyReturnsUnauthorized(): void
    {
        self::getClient()->request('GET', '/api/v2/analytics/dashboard/summary');
        $this->assertHttpUnauthorized();
    }

    public function testGetDashboardSummaryWithValidSessionReturnsCardsData(): void
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            AdministratorTokenFixture::class,
            SubscriberFixture::class,
            MessageFixture::class,
        ]);

        $this->authenticatedJsonRequest('GET', '/api/v2/analytics/dashboard/summary');
        $this->assertHttpOkay();
        $response = $this->getDecodedJsonResponseContent();

        foreach (['total_subscribers', 'active_campaigns', 'open_rate', 'bounce_rate'] as $metric) {
            self::assertIsArray($response[$metric]);
            self::assertArrayHasKey('value', $response[$metric]);
            self::assertArrayHasKey('change_vs_last_month', $response[$metric]);
            self::assertIsNumeric($response[$metric]['value']);
            self::assertIsNumeric($response[$metric]['change_vs_last_month']);
        }
    }

    public function testGetRecentCampaignsStatisticsWithoutSessionKeyReturnsUnauthorized(): void
    {
        self::getClient()->request('GET', '/api/v2/analytics/dashboard/recent-campaigns');
        $this->assertHttpUnauthorized();
    }

    public function testGetRecentCampaignsStatisticsWithValidSessionReturnsCampaignsData(): void
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            AdministratorTokenFixture::class,
            SubscriberFixture::class,
            MessageFixture::class,
        ]);

        $this->authenticatedJsonRequest('GET', '/api/v2/analytics/dashboard/recent-campaigns');
        $this->assertHttpOkay();
        $response = $this->getDecodedJsonResponseContent();
    }

    public function testGetCampaignPerformanceStatisticsWithoutSessionKeyReturnsUnauthorized(): void
    {
        self::getClient()->request('GET', '/api/v2/analytics/dashboard/performance');
        $this->assertHttpUnauthorized();
    }

    public function testGetCampaignPerformanceStatisticsWithValidSessionReturnsPerformanceData(): void
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            AdministratorTokenFixture::class,
            SubscriberFixture::class,
            MessageFixture::class,
        ]);

        $this->authenticatedJsonRequest('GET', '/api/v2/analytics/dashboard/performance');
        $this->assertHttpOkay();
        $this->getDecodedJsonResponseContent();
    }
}
