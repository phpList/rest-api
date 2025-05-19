<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Subscription\Model\Filter\SubscriberFilter;
use PhpList\Core\Domain\Subscription\Model\Subscriber;
use PhpList\Core\Domain\Subscription\Model\SubscriberList;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Service\Provider\PaginatedDataProvider;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Subscription\Serializer\SubscriberNormalizer;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/lists', name: 'list_members_')]
class ListMembersController extends BaseController
{
    private SubscriberNormalizer $subscriberNormalizer;
    private PaginatedDataProvider $paginatedProvider;

    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        SubscriberNormalizer $subscriberNormalizer,
        PaginatedDataProvider $paginatedProvider,
    ) {
        parent::__construct($authentication, $validator);
        $this->subscriberNormalizer = $subscriberNormalizer;
        $this->paginatedProvider = $paginatedProvider;
    }

    #[Route('/{listId}/subscribers', name: 'get_list', requirements: ['listId' => '\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/lists/{listId}/subscribers',
        description: 'Returns a JSON list of all subscribers for a subscriber list.',
        summary: 'Gets a list of all subscribers of a subscriber list.',
        tags: ['subscriptions'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID obtained from authentication',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'listId',
                description: 'List ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'after_id',
                description: 'Last id (starting from 0)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Number of results per page',
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
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Subscriber')
                        ),
                        new OA\Property(property: 'pagination', ref: '#/components/schemas/CursorPagination')
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundErrorResponse')
            )
        ]
    )]
    public function getListMembers(
        Request $request,
        #[MapEntity(mapping: ['listId' => 'id'])] ?SubscriberList $list = null,
    ): JsonResponse {
        $this->requireAuthentication($request);

        if (!$list) {
            throw $this->createNotFoundException('Subscriber list not found.');
        }

        return $this->json(
            $this->paginatedProvider->getPaginatedList(
                $request,
                $this->subscriberNormalizer,
                Subscriber::class,
                (new SubscriberFilter())->setListId($list->getId())
            ),
            Response::HTTP_OK
        );
    }

    #[Route('/{listId}/subscribers/count', name: 'get_count', requirements: ['listId' => '\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/lists/{listId}/count',
        description: 'Returns a count of all subscribers in a given list.',
        summary: 'Gets the total number of subscribers of a list',
        tags: ['subscriptions'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID obtained from authentication',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'listId',
                description: 'List ID',
                in: 'path',
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
                            property: 'subscribers_count',
                            type: 'integer',
                            example: 42
                        )
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            )
        ]
    )]
    public function getSubscribersCount(
        Request $request,
        #[MapEntity(mapping: ['listId' => 'id'])] ?SubscriberList $list = null,
    ): JsonResponse {
        $this->requireAuthentication($request);

        if (!$list) {
            throw $this->createNotFoundException('Subscriber list not found.');
        }

        return $this->json(['subscribers_count' => count($list->getSubscribers())], Response::HTTP_OK);
    }
}
