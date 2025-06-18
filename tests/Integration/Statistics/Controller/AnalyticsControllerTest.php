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
        self::assertArrayHasKey('items', $response);
        self::assertArrayHasKey('pagination', $response);
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
        self::assertArrayHasKey('items', $response);
        self::assertArrayHasKey('pagination', $response);
        self::assertIsArray($response['items']);
        self::assertIsArray($response['pagination']);
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
        self::assertIsArray($response['domains']);
        self::assertIsInt($response['total']);
    }

    public function testGetTopDomainsWithLimitParameter(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class, SubscriberFixture::class]);

        $this->authenticatedJsonRequest('GET', '/api/v2/analytics/domains/top?limit=5');
        $response = $this->getDecodedJsonResponseContent();

        self::assertIsArray($response);
        self::assertArrayHasKey('domains', $response);
        self::assertIsArray($response['domains']);
        self::assertLessThanOrEqual(5, count($response['domains']));
    }

    public function testGetTopDomainsWithMinSubscribersParameter(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class, SubscriberFixture::class]);

        $this->authenticatedJsonRequest('GET', '/api/v2/analytics/domains/top?min_subscribers=10');
        $response = $this->getDecodedJsonResponseContent();

        self::assertIsArray($response);
        self::assertArrayHasKey('domains', $response);
        self::assertIsArray($response['domains']);

        // Verify all domains have at least 10 subscribers
        foreach ($response['domains'] as $domain) {
            self::assertArrayHasKey('subscribers', $domain);
            self::assertGreaterThanOrEqual(10, $domain['subscribers']);
        }
    }

    public function testGetTopDomainsWithBothParameters(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class, SubscriberFixture::class]);

        $this->authenticatedJsonRequest('GET', '/api/v2/analytics/domains/top?limit=3&min_subscribers=10');
        $response = $this->getDecodedJsonResponseContent();

        self::assertIsArray($response);
        self::assertArrayHasKey('domains', $response);
        self::assertIsArray($response['domains']);
        self::assertLessThanOrEqual(3, count($response['domains']));

        foreach ($response['domains'] as $domain) {
            self::assertArrayHasKey('subscribers', $domain);
            self::assertGreaterThanOrEqual(10, $domain['subscribers']);
        }
    }

    public function testGetTopDomainsWithInvalidLimitParameter(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class, SubscriberFixture::class]);

        $this->authenticatedJsonRequest('GET', '/api/v2/analytics/domains/top?limit=invalid');
        $response = $this->getDecodedJsonResponseContent();

        self::assertIsArray($response);
        self::assertArrayHasKey('domains', $response);
        self::assertIsArray($response['domains']);
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
        self::assertArrayHasKey('local_parts', $response);
        self::assertArrayHasKey('total', $response);
        self::assertIsArray($response['local_parts']);
        self::assertIsInt($response['total']);
    }

    public function testGetTopLocalPartsWithLimitParameter(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class, SubscriberFixture::class]);

        $this->authenticatedJsonRequest('GET', '/api/v2/analytics/local-parts/top?limit=5');
        $response = $this->getDecodedJsonResponseContent();

        self::assertIsArray($response);
        self::assertArrayHasKey('local_parts', $response);
        self::assertIsArray($response['local_parts']);
        self::assertLessThanOrEqual(5, count($response['local_parts']));
    }

    public function testGetTopLocalPartsWithInvalidLimitParameter(): void
    {
        $this->loadFixtures([AdministratorFixture::class, AdministratorTokenFixture::class, SubscriberFixture::class]);

        $this->authenticatedJsonRequest('GET', '/api/v2/analytics/local-parts/top?limit=invalid');
        $response = $this->getDecodedJsonResponseContent();

        self::assertIsArray($response);
        self::assertArrayHasKey('local_parts', $response);
        self::assertIsArray($response['local_parts']);
    }
}
