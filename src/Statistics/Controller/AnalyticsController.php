<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Statistics\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Analytics\Service\AnalyticsService;
use PhpList\Core\Domain\Identity\Model\PrivilegeFlag;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Statistics\Serializer\CampaignStatisticsNormalizer;
use PhpList\RestBundle\Statistics\Serializer\TopDomainsNormalizer;
use PhpList\RestBundle\Statistics\Serializer\TopLocalPartsNormalizer;
use PhpList\RestBundle\Statistics\Serializer\ViewOpensStatisticsNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * This controller provides REST API to access analytics data.
 */
#[Route('/analytics', name: 'analytics_')]
class AnalyticsController extends BaseController
{
    public const BATCH_SIZE = 20;
    private AnalyticsService $analyticsService;
    private CampaignStatisticsNormalizer $campaignStatsNormalizer;
    private ViewOpensStatisticsNormalizer $viewOpensStatsNormalizer;
    private TopDomainsNormalizer $topDomainsNormalizer;
    private TopLocalPartsNormalizer $topLocalPartsNormalizer;

    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        AnalyticsService $analyticsService,
        CampaignStatisticsNormalizer $campaignStatsNormalizer,
        ViewOpensStatisticsNormalizer $viewOpensStatsNormalizer,
        TopDomainsNormalizer $topDomainsNormalizer,
        TopLocalPartsNormalizer $topLocalPartsNormalizer
    ) {
        parent::__construct($authentication, $validator);
        $this->analyticsService = $analyticsService;
        $this->campaignStatsNormalizer = $campaignStatsNormalizer;
        $this->viewOpensStatsNormalizer = $viewOpensStatsNormalizer;
        $this->topDomainsNormalizer = $topDomainsNormalizer;
        $this->topLocalPartsNormalizer = $topLocalPartsNormalizer;
    }

    #[Route('/campaigns', name: 'campaign_statistics', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/analytics/campaigns',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns statistics overview for campaigns.',
        summary: 'Gets campaign statistics.',
        tags: ['analytics'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Maximum number of campaigns to return',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 20, minimum: 1)
            ),
            new OA\Parameter(
                name: 'after_id',
                description: 'Last seen campaign ID for pagination',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 0, minimum: 0)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/CampaignStatistics')
                        ),
                        new OA\Property(property: 'pagination', ref: '#/components/schemas/CursorPagination')
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            )
        ]
    )]
    public function getCampaignStatistics(Request $request): JsonResponse
    {
        $authUser = $this->requireAuthentication($request);
        if (!$authUser->getPrivileges()->has(PrivilegeFlag::Statistics)) {
            throw $this->createAccessDeniedException('You are not allowed to access statistics.');
        }

        $limit = (int) $request->query->get('limit', self::BATCH_SIZE);
        $lastId = (int) $request->query->get('after_id', 0);

        $data = $this->analyticsService->getCampaignStatistics($limit, $lastId);
        $normalizedData = $this->campaignStatsNormalizer->normalize($data, null, [
            'limit' => $limit,
            'campaign_statistics' => true,
        ]);

        return $this->json($normalizedData, Response::HTTP_OK);
    }

    #[Route('/view-opens', name: 'view_opens_statistics', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/analytics/view-opens',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns statistics for view opens.',
        summary: 'Gets view opens statistics.',
        tags: ['analytics'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Maximum number of campaigns to return',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 20, minimum: 1)
            ),
            new OA\Parameter(
                name: 'after_id',
                description: 'Last seen campaign ID for pagination',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 0, minimum: 0)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/ViewOpensStatistics')
                        ),
                        new OA\Property(property: 'pagination', ref: '#/components/schemas/CursorPagination')
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            )
        ]
    )]
    public function getViewOpensStatistics(Request $request): JsonResponse
    {
        $authUser = $this->requireAuthentication($request);
        if (!$authUser->getPrivileges()->has(PrivilegeFlag::Statistics)) {
            throw $this->createAccessDeniedException('You are not allowed to access statistics.');
        }

        $limit = (int) $request->query->get('limit', self::BATCH_SIZE);
        $lastId = (int) $request->query->get('after_id', 0);

        $data = $this->analyticsService->getViewOpensStatistics($limit, $lastId);
        $normalizedData = $this->viewOpensStatsNormalizer->normalize($data, null, [
            'view_opens_statistics' => true,
            'limit' => $limit
        ]);

        return $this->json($normalizedData, Response::HTTP_OK);
    }

    #[Route('/domains/top', name: 'top_domains', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/analytics/domains/top',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns statistics for the top domains with more than 5 subscribers.',
        summary: 'Gets top domains statistics.',
        tags: ['analytics'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Maximum number of domains to return',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 20, minimum: 1)
            ),
            new OA\Parameter(
                name: 'min_subscribers',
                description: 'Minimum number of subscribers per domain',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 5, minimum: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/TopDomainStats')
            ),
            new OA\Response(
                response: 403,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            )
        ]
    )]
    public function getTopDomains(Request $request): JsonResponse
    {
        $authUser = $this->requireAuthentication($request);
        if (!$authUser->getPrivileges()->has(PrivilegeFlag::Statistics)) {
            throw $this->createAccessDeniedException('You are not allowed to access statistics.');
        }

        $limit = (int) $request->query->get('limit', self::BATCH_SIZE);
        $minSubscribers = (int) $request->query->get('min_subscribers', 5);

        $data = $this->analyticsService->getTopDomains($limit, $minSubscribers);
        $normalizedData = $this->topDomainsNormalizer->normalize($data, null, [
            'top_domains' => true,
        ]);

        return $this->json($normalizedData, Response::HTTP_OK);
    }

    #[Route('/domains/confirmation', name: 'domain_confirmation_statistics', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/analytics/domains/confirmation',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns statistics for domains showing confirmation status.',
        summary: 'Gets domain confirmation statistics.',
        tags: ['analytics'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Maximum number of domains to return',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 50, maximum: 100, minimum: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/DetailedDomainStats')
            ),
            new OA\Response(
                response: 403,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            )
        ]
    )]
    public function getDomainConfirmationStatistics(Request $request): JsonResponse
    {
        $authUser = $this->requireAuthentication($request);
        if (!$authUser->getPrivileges()->has(PrivilegeFlag::Statistics)) {
            throw $this->createAccessDeniedException('You are not allowed to access statistics.');
        }

        $limit = (int) $request->query->get('limit', 50);

        $data = $this->analyticsService->getDomainConfirmationStatistics($limit);

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/local-parts/top', name: 'top_local_parts', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/analytics/local-parts/top',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns statistics for the top local-parts of email addresses.',
        summary: 'Gets top local-parts statistics.',
        tags: ['analytics'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Maximum number of local-parts to return',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 25, maximum: 100, minimum: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/LocalPartsStats')
            ),
            new OA\Response(
                response: 403,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            )
        ]
    )]
    public function getTopLocalParts(Request $request): JsonResponse
    {
        $authUser = $this->requireAuthentication($request);
        if (!$authUser->getPrivileges()->has(PrivilegeFlag::Statistics)) {
            throw $this->createAccessDeniedException('You are not allowed to access statistics.');
        }

        $limit = (int) $request->query->get('limit', 25);

        $data = $this->analyticsService->getTopLocalParts($limit);
        $normalizedData = $this->topLocalPartsNormalizer->normalize($data, null, [
            'top_local_parts' => true,
        ]);

        return $this->json($normalizedData, Response::HTTP_OK);
    }

    #[Route('/dashboard/summary', name: 'dashboard_summary', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/analytics/dashboard/summary',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns dashboard summary statistics.',
        summary: 'Gets dashboard summary statistics.',
        tags: ['analytics'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'total_subscribers',
                            properties: [
                                new OA\Property(property: 'value', type: 'integer', example: 48294),
                                new OA\Property(
                                    property: 'change_vs_last_month',
                                    type: 'number',
                                    format: 'float',
                                    example: 12.5
                                ),
                            ],
                            type: 'object'
                        ),
                        new OA\Property(
                            property: 'active_campaigns',
                            properties: [
                                new OA\Property(property: 'value', type: 'integer', example: 12),
                                new OA\Property(
                                    property: 'change_vs_last_month',
                                    type: 'number',
                                    format: 'float',
                                    example: 0
                                ),
                            ],
                            type: 'object'
                        ),
                        new OA\Property(
                            property: 'open_rate',
                            properties: [
                                new OA\Property(
                                    property: 'value',
                                    type: 'number',
                                    format: 'float',
                                    example: 12
                                ),
                                new OA\Property(
                                    property: 'change_vs_last_month',
                                    type: 'number',
                                    format: 'float',
                                    example: 0
                                ),
                            ],
                            type: 'object'
                        ),
                        new OA\Property(
                            property: 'bounce_rate',
                            properties: [
                                new OA\Property(
                                    property: 'value',
                                    type: 'number',
                                    format: 'float',
                                    example: 12
                                ),
                                new OA\Property(
                                    property: 'change_vs_last_month',
                                    type: 'number',
                                    format: 'float',
                                    example: 0
                                ),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Not authenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            )
        ]
    )]
    public function getDashboardSummary(Request $request): JsonResponse
    {
        $this->requireAuthentication($request);

        $data = $this->analyticsService->getSummaryStatistics();

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/dashboard/recent-campaigns', name: 'dashboard_recent_campaigns', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/analytics/dashboard/recent-campaigns',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns the most recent campaigns with their performance metrics.',
        summary: 'Gets dashboard recent campaigns statistics.',
        tags: ['analytics'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'name', type: 'string', example: 'March Newsletter'),
                            new OA\Property(
                                property: 'status',
                                type: 'string',
                                example: 'sent',
                                nullable: true
                            ),
                            new OA\Property(
                                property: 'date',
                                type: 'string',
                                format: 'date',
                                example: '2026-03-15',
                                nullable: true
                            ),
                            new OA\Property(property: 'open_rate', type: 'string', example: '42.50%'),
                            new OA\Property(property: 'click_rate', type: 'string', example: '8.10%'),
                        ],
                        type: 'object'
                    )
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Not authenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            )
        ]
    )]
    public function getRecentCampaignsStatistics(Request $request): JsonResponse
    {
        $this->requireAuthentication($request);

        $data = $this->analyticsService->getRecentCampaigns();

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/dashboard/performance', name: 'dashboard_performance', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/analytics/dashboard/performance',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns campaign performance metrics over time.',
        summary: 'Gets dashboard campaign performance statistics.',
        tags: ['analytics'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(
                                property: 'date',
                                type: 'string',
                                format: 'date',
                                example: '2026-03-19'
                            ),
                            new OA\Property(property: 'opens', type: 'integer', example: 234),
                            new OA\Property(property: 'clicks', type: 'integer', example: 57),
                        ],
                        type: 'object'
                    )
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Not authenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            )
        ]
    )]
    public function getCampaignPerformanceStatistics(Request $request): JsonResponse
    {
        $this->requireAuthentication($request);

        $data = $this->analyticsService->getCampaignPerformance();

        return $this->json($data, Response::HTTP_OK);
    }
}
