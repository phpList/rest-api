<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Statistics\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Analytics\Service\AnalyticsService;
use PhpList\Core\Domain\Identity\Model\PrivilegeFlag;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Validator\RequestValidator;
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
    private AnalyticsService $analyticsService;

    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        AnalyticsService $analyticsService
    ) {
        parent::__construct($authentication, $validator);
        $this->analyticsService = $analyticsService;
    }

    #[Route('/campaigns', name: 'campaign_statistics', methods: ['GET'])]
    #[OA\Get(
        path: '/analytics/campaigns',
        description: 'Returns statistics overview for campaigns.',
        summary: 'Gets campaign statistics.',
        tags: ['analytics'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID obtained from authentication',
                in: 'header',
                required: true,
                schema: new OA\Schema(
                    type: 'string'
                )
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Maximum number of campaigns to return',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 50, maximum: 100, minimum: 1)
            ),
            new OA\Parameter(
                name: 'last_id',
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
                            property: 'campaigns',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'campaignId', type: 'integer'),
                                    new OA\Property(property: 'subject', type: 'string'),
                                    new OA\Property(property: 'dateSent', type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'sent', type: 'integer'),
                                    new OA\Property(property: 'bounces', type: 'integer'),
                                    new OA\Property(property: 'forwards', type: 'integer'),
                                    new OA\Property(property: 'uniqueViews', type: 'integer'),
                                    new OA\Property(property: 'totalClicks', type: 'integer'),
                                    new OA\Property(property: 'uniqueClicks', type: 'integer'),
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(property: 'total', type: 'integer'),
                        new OA\Property(property: 'hasMore', type: 'boolean'),
                        new OA\Property(property: 'lastId', type: 'integer'),
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

        $limit = (int) $request->query->get('limit', 50);
        $lastId = (int) $request->query->get('last_id', 0);

        $data = $this->analyticsService->getCampaignStatistics($limit, $lastId);

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/view-opens', name: 'view_opens_statistics', methods: ['GET'])]
    #[OA\Get(
        path: '/analytics/view-opens',
        description: 'Returns statistics for view opens.',
        summary: 'Gets view opens statistics.',
        tags: ['analytics'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID obtained from authentication',
                in: 'header',
                required: true,
                schema: new OA\Schema(
                    type: 'string'
                )
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Maximum number of campaigns to return',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 50, maximum: 100, minimum: 1)
            ),
            new OA\Parameter(
                name: 'last_id',
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
                            property: 'campaigns',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'campaignId', type: 'integer'),
                                    new OA\Property(property: 'subject', type: 'string'),
                                    new OA\Property(property: 'sent', type: 'integer'),
                                    new OA\Property(property: 'uniqueViews', type: 'integer'),
                                    new OA\Property(property: 'rate', type: 'number', format: 'float'),
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(property: 'total', type: 'integer'),
                        new OA\Property(property: 'hasMore', type: 'boolean'),
                        new OA\Property(property: 'lastId', type: 'integer'),
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

        $limit = (int) $request->query->get('limit', 50);
        $lastId = (int) $request->query->get('last_id', 0);

        $data = $this->analyticsService->getViewOpensStatistics($limit, $lastId);

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/domains/top', name: 'top_domains', methods: ['GET'])]
    #[OA\Get(
        path: '/analytics/domains/top',
        description: 'Returns statistics for the top domains with more than 5 subscribers.',
        summary: 'Gets top domains statistics.',
        tags: ['analytics'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID obtained from authentication',
                in: 'header',
                required: true,
                schema: new OA\Schema(
                    type: 'string'
                )
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Maximum number of domains to return',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 50, maximum: 100, minimum: 1)
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
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'domains',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'domain', type: 'string'),
                                    new OA\Property(property: 'subscribers', type: 'integer'),
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(property: 'total', type: 'integer'),
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
    public function getTopDomains(Request $request): JsonResponse
    {
        $authUser = $this->requireAuthentication($request);
        if (!$authUser->getPrivileges()->has(PrivilegeFlag::Statistics)) {
            throw $this->createAccessDeniedException('You are not allowed to access statistics.');
        }

        $limit = (int) $request->query->get('limit', 50);
        $minSubscribers = (int) $request->query->get('min_subscribers', 5);

        $data = $this->analyticsService->getTopDomains($limit, $minSubscribers);

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/domains/confirmation', name: 'domain_confirmation_statistics', methods: ['GET'])]
    #[OA\Get(
        path: '/analytics/domains/confirmation',
        description: 'Returns statistics for domains showing confirmation status.',
        summary: 'Gets domain confirmation statistics.',
        tags: ['analytics'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID obtained from authentication',
                in: 'header',
                required: true,
                schema: new OA\Schema(
                    type: 'string'
                )
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
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'domains',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'domain', type: 'string'),
                                    new OA\Property(
                                        property: 'confirmed',
                                        properties: [
                                            new OA\Property(property: 'count', type: 'integer'),
                                            new OA\Property(property: 'percentage', type: 'number', format: 'float'),
                                        ],
                                        type: 'object'
                                    ),
                                    new OA\Property(
                                        property: 'unconfirmed',
                                        properties: [
                                            new OA\Property(property: 'count', type: 'integer'),
                                            new OA\Property(property: 'percentage', type: 'number', format: 'float'),
                                        ],
                                        type: 'object'
                                    ),
                                    new OA\Property(
                                        property: 'blacklisted',
                                        properties: [
                                            new OA\Property(property: 'count', type: 'integer'),
                                            new OA\Property(property: 'percentage', type: 'number', format: 'float'),
                                        ],
                                        type: 'object'
                                    ),
                                    new OA\Property(
                                        property: 'total',
                                        properties: [
                                            new OA\Property(property: 'count', type: 'integer'),
                                            new OA\Property(property: 'percentage', type: 'number', format: 'float'),
                                        ],
                                        type: 'object'
                                    ),
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(property: 'total', type: 'integer'),
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
        path: '/analytics/local-parts/top',
        description: 'Returns statistics for the top local-parts of email addresses.',
        summary: 'Gets top local-parts statistics.',
        tags: ['analytics'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID obtained from authentication',
                in: 'header',
                required: true,
                schema: new OA\Schema(
                    type: 'string'
                )
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
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'localParts',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'localPart', type: 'string'),
                                    new OA\Property(property: 'count', type: 'integer'),
                                    new OA\Property(property: 'percentage', type: 'number', format: 'float'),
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(property: 'total', type: 'integer'),
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
    public function getTopLocalParts(Request $request): JsonResponse
    {
        $authUser = $this->requireAuthentication($request);
        if (!$authUser->getPrivileges()->has(PrivilegeFlag::Statistics)) {
            throw $this->createAccessDeniedException('You are not allowed to access statistics.');
        }

        $limit = (int) $request->query->get('limit', 25);

        $data = $this->analyticsService->getTopLocalParts($limit);

        return $this->json($data, Response::HTTP_OK);
    }
}
