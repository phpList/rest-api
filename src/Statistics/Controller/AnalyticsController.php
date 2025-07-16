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
}
