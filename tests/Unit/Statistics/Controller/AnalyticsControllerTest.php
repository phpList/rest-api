<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Statistics\Controller;

use PhpList\Core\Domain\Analytics\Service\AnalyticsService;
use PhpList\Core\Domain\Identity\Model\Administrator;
use PhpList\Core\Domain\Identity\Model\PrivilegeFlag;
use PhpList\Core\Domain\Identity\Model\Privileges;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Statistics\Controller\AnalyticsController;
use PhpList\RestBundle\Statistics\Serializer\CampaignStatisticsNormalizer;
use PhpList\RestBundle\Statistics\Serializer\ViewOpensStatisticsNormalizer;
use PhpList\RestBundle\Tests\Helpers\DummyAnalyticsController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AnalyticsControllerTest extends TestCase
{
    private Authentication|MockObject $authentication;
    private AnalyticsService|MockObject $analyticsService;
    private AnalyticsController $controller;
    private Administrator|MockObject $administrator;
    private Privileges|MockObject $privileges;

    protected function setUp(): void
    {
        $this->authentication = $this->createMock(Authentication::class);
        $validator = $this->createMock(RequestValidator::class);
        $this->analyticsService = $this->createMock(AnalyticsService::class);
        $campaignStatisticsNormalizer = new CampaignStatisticsNormalizer();
        $viewOpensStatisticsNormalizer = new ViewOpensStatisticsNormalizer();
        $this->controller = new DummyAnalyticsController(
            $this->authentication,
            $validator,
            $this->analyticsService,
            $campaignStatisticsNormalizer,
            $viewOpensStatisticsNormalizer,
        );

        $this->privileges = $this->createMock(Privileges::class);
        $this->administrator = $this->createMock(Administrator::class);
        $this->administrator->method('getPrivileges')->willReturn($this->privileges);
    }

    public function testGetCampaignStatisticsWithoutStatisticsPrivilegeThrowsException(): void
    {
        $request = new Request();

        $this->authentication
            ->expects(self::once())
            ->method('authenticateByApiKey')
            ->with($request)
            ->willReturn($this->administrator);

        $this->privileges
            ->expects(self::once())
            ->method('has')
            ->with(PrivilegeFlag::Statistics)
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('You are not allowed to access statistics.');

        $this->controller->getCampaignStatistics($request);
    }

    public function testGetCampaignStatisticsReturnsJsonResponse(): void
    {
        $request = new Request();
        $request->query->set('limit', '20');
        $request->query->set('after_id', '10');

        $serviceData = [
            'campaigns' => [
                [
                    'campaignId' => 1,
                    'subject' => 'Test Campaign',
                    'dateSent' => '2023-01-01T00:00:00+00:00',
                    'sent' => 100,
                    'bounces' => 5,
                    'forwards' => 2,
                    'uniqueViews' => 80,
                    'totalClicks' => 150,
                    'uniqueClicks' => 70,
                ]
            ],
            'total' => 1,
            'hasMore' => false,
            'lastId' => 1,
        ];

        $normalizedData = [
            'items' => [
                [
                    'campaign_id' => 1,
                    'subject' => 'Test Campaign',
                    'date_sent' => '2023-01-01T00:00:00+00:00',
                    'sent' => 100,
                    'bounces' => 5,
                    'forwards' => 2,
                    'unique_views' => 80,
                    'total_clicks' => 150,
                    'unique_clicks' => 70,
                ]
            ],
            'pagination' => [
                'total' => 1,
                'limit' => 20,
                'has_more' => false,
                'next_cursor' => 2,
            ],
        ];

        $this->authentication
            ->expects(self::once())
            ->method('authenticateByApiKey')
            ->with($request)
            ->willReturn($this->administrator);

        $this->privileges
            ->expects(self::once())
            ->method('has')
            ->with(PrivilegeFlag::Statistics)
            ->willReturn(true);

        $this->analyticsService
            ->expects(self::once())
            ->method('getCampaignStatistics')
            ->with(20, 10)
            ->willReturn($serviceData);

        $response = $this->controller->getCampaignStatistics($request);

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertEquals($normalizedData, json_decode($response->getContent(), true));
    }

    public function testGetViewOpensStatisticsWithoutStatisticsPrivilegeThrowsException(): void
    {
        $request = new Request();

        $this->authentication
            ->expects(self::once())
            ->method('authenticateByApiKey')
            ->with($request)
            ->willReturn($this->administrator);

        $this->privileges
            ->expects(self::once())
            ->method('has')
            ->with(PrivilegeFlag::Statistics)
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('You are not allowed to access statistics.');

        $this->controller->getViewOpensStatistics($request);
    }

    public function testGetViewOpensStatisticsReturnsJsonResponse(): void
    {
        $request = new Request();
        $request->query->set('limit', '20');
        $request->query->set('after_id', '10');

        $expectedData = [
            'campaigns' => [
                [
                    'campaignId' => 1,
                    'subject' => 'Test Campaign',
                    'sent' => 100,
                    'uniqueViews' => 80,
                    'rate' => 80.0,
                ]
            ],
            'total' => 1,
            'hasMore' => false,
            'lastId' => 1,
        ];

        $normalizedData = [
            'items' => [
                [
                    'campaign_id' => 1,
                    'subject' => 'Test Campaign',
                    'sent' => 100,
                    'unique_views' => 80,
                    'rate' => 80,
                ]
            ],
            'pagination' => [
                'total' => 1,
                'limit' => 20,
                'has_more' => false,
                'next_cursor' => 2,
            ],
        ];

        $this->authentication
            ->expects(self::once())
            ->method('authenticateByApiKey')
            ->with($request)
            ->willReturn($this->administrator);

        $this->privileges
            ->expects(self::once())
            ->method('has')
            ->with(PrivilegeFlag::Statistics)
            ->willReturn(true);

        $this->analyticsService
            ->expects(self::once())
            ->method('getViewOpensStatistics')
            ->with(20, 10)
            ->willReturn($expectedData);

        $response = $this->controller->getViewOpensStatistics($request);

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertEquals($normalizedData, json_decode($response->getContent(), true));
    }

    public function testGetTopDomainsWithoutStatisticsPrivilegeThrowsException(): void
    {
        $request = new Request();

        $this->authentication
            ->expects(self::once())
            ->method('authenticateByApiKey')
            ->with($request)
            ->willReturn($this->administrator);

        $this->privileges
            ->expects(self::once())
            ->method('has')
            ->with(PrivilegeFlag::Statistics)
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('You are not allowed to access statistics.');

        $this->controller->getTopDomains($request);
    }

    public function testGetTopDomainsReturnsJsonResponse(): void
    {
        $request = new Request();
        $request->query->set('limit', '20');
        $request->query->set('min_subscribers', '10');

        $expectedData = [
            'domains' => [
                [
                    'domain' => 'example.com',
                    'subscribers' => 50,
                ]
            ],
            'total' => 1,
        ];

        $this->authentication
            ->expects(self::once())
            ->method('authenticateByApiKey')
            ->with($request)
            ->willReturn($this->administrator);

        $this->privileges
            ->expects(self::once())
            ->method('has')
            ->with(PrivilegeFlag::Statistics)
            ->willReturn(true);

        $this->analyticsService
            ->expects(self::once())
            ->method('getTopDomains')
            ->with(20, 10)
            ->willReturn($expectedData);

        $response = $this->controller->getTopDomains($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertEquals($expectedData, json_decode($response->getContent(), true));
    }

    public function testGetDomainConfirmationStatisticsWithoutStatisticsPrivilegeThrowsException(): void
    {
        $request = new Request();

        $this->authentication
            ->expects(self::once())
            ->method('authenticateByApiKey')
            ->with($request)
            ->willReturn($this->administrator);

        $this->privileges
            ->expects(self::once())
            ->method('has')
            ->with(PrivilegeFlag::Statistics)
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('You are not allowed to access statistics.');

        $this->controller->getDomainConfirmationStatistics($request);
    }

    public function testGetDomainConfirmationStatisticsReturnsJsonResponse(): void
    {
        $request = new Request();
        $request->query->set('limit', '20');

        $expectedData = [
            'domains' => [
                [
                    'domain' => 'example.com',
                    'confirmed' => [
                        'count' => 40,
                        'percentage' => 80.0,
                    ],
                    'unconfirmed' => [
                        'count' => 5,
                        'percentage' => 10.0,
                    ],
                    'blacklisted' => [
                        'count' => 5,
                        'percentage' => 10.0,
                    ],
                    'total' => [
                        'count' => 50,
                        'percentage' => 100.0,
                    ],
                ]
            ],
            'total' => 1,
        ];

        $this->authentication
            ->expects(self::once())
            ->method('authenticateByApiKey')
            ->with($request)
            ->willReturn($this->administrator);

        $this->privileges
            ->expects(self::once())
            ->method('has')
            ->with(PrivilegeFlag::Statistics)
            ->willReturn(true);

        $this->analyticsService
            ->expects(self::once())
            ->method('getDomainConfirmationStatistics')
            ->with(20)
            ->willReturn($expectedData);

        $response = $this->controller->getDomainConfirmationStatistics($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertEquals($expectedData, json_decode($response->getContent(), true));
    }

    public function testGetTopLocalPartsWithoutStatisticsPrivilegeThrowsException(): void
    {
        $request = new Request();

        $this->authentication
            ->expects(self::once())
            ->method('authenticateByApiKey')
            ->with($request)
            ->willReturn($this->administrator);

        $this->privileges
            ->expects(self::once())
            ->method('has')
            ->with(PrivilegeFlag::Statistics)
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('You are not allowed to access statistics.');

        $this->controller->getTopLocalParts($request);
    }

    public function testGetTopLocalPartsReturnsJsonResponse(): void
    {
        $request = new Request();
        $request->query->set('limit', '20');

        $expectedData = [
            'localParts' => [
                [
                    'localPart' => 'info',
                    'count' => 30,
                    'percentage' => 60.0,
                ]
            ],
            'total' => 1,
        ];

        $this->authentication
            ->expects(self::once())
            ->method('authenticateByApiKey')
            ->with($request)
            ->willReturn($this->administrator);

        $this->privileges
            ->expects(self::once())
            ->method('has')
            ->with(PrivilegeFlag::Statistics)
            ->willReturn(true);

        $this->analyticsService
            ->expects(self::once())
            ->method('getTopLocalParts')
            ->with(20)
            ->willReturn($expectedData);

        $response = $this->controller->getTopLocalParts($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertEquals($expectedData, json_decode($response->getContent(), true));
    }
}
