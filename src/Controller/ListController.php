<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Model\Subscription\SubscriberList;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Entity\Request\CreateSubscriberListRequest;
use PhpList\RestBundle\Serializer\SubscriberListNormalizer;
use PhpList\RestBundle\Service\Manager\SubscriberListManager;
use PhpList\RestBundle\Service\Provider\PaginatedDataProvider;
use PhpList\RestBundle\Validator\RequestValidator;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This controller provides REST API access to subscriber lists.
 *
 * @author Oliver Klee <oliver@phplist.com>
 * @author Xheni Myrtaj <xheni@phplist.com>
 * @author Tatevik Grigoryan <tatevik@phplist.com>
 */
#[Route('/lists')]
class ListController extends BaseController
{
    private SubscriberListNormalizer $normalizer;
    private SubscriberListManager $subscriberListManager;
    private PaginatedDataProvider $paginatedDataProvider;

    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        SubscriberListNormalizer $normalizer,
        SubscriberListManager $subscriberListManager,
        PaginatedDataProvider $paginatedDataProvider,
    ) {
        parent::__construct($authentication, $validator);
        $this->normalizer = $normalizer;
        $this->subscriberListManager = $subscriberListManager;
        $this->paginatedDataProvider = $paginatedDataProvider;
    }

    #[Route('', name: 'get_lists', methods: ['GET'])]
    #[OA\Get(
        path: '/lists',
        description: 'Returns a JSON list of all subscriber lists.',
        summary: 'Gets a list of all subscriber lists.',
        tags: ['lists'],
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
                            items: new OA\Items(ref: '#/components/schemas/SubscriberList')
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
            )
        ]
    )]
    public function getLists(Request $request): JsonResponse
    {
        $this->requireAuthentication($request);

        return new JsonResponse(
            $this->paginatedDataProvider->getPaginatedList($request, $this->normalizer, SubscriberList::class),
            Response::HTTP_OK
        );
    }

    #[Route('/{listId}', name: 'get_list', methods: ['GET'])]
    #[OA\Get(
        path: '/lists/{listId}',
        description: 'Returns a single subscriber list with specified ID.',
        summary: 'Gets a subscriber list.',
        tags: ['lists'],
        parameters: [
            new OA\Parameter(
                name: 'listId',
                description: 'List ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'session',
                description: 'Session ID obtained from authentication',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/SubscriberList')
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Failure',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'There is no list with that ID.'
                        )
                    ],
                    type: 'object'
                )
            )
        ]
    )]
    public function getList(
        Request $request,
        #[MapEntity(mapping: ['listId' => 'id'])] ?SubscriberList $list = null
    ): JsonResponse {
        $this->requireAuthentication($request);

        if (!$list) {
            throw new NotFoundHttpException('Subscriber list not found.');
        }

        return new JsonResponse($this->normalizer->normalize($list), Response::HTTP_OK);
    }

    #[Route('/{listId}', name: 'delete_list', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/lists/{listId}',
        description: 'Deletes a single subscriber list.',
        summary: 'Deletes a list.',
        tags: ['lists'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID',
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
                description: 'Success'
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
    public function deleteList(
        Request $request,
        #[MapEntity(mapping: ['listId' => 'id'])] ?SubscriberList $list = null
    ): JsonResponse {
        $this->requireAuthentication($request);

        if (!$list) {
            throw new NotFoundHttpException('Subscriber list not found.');
        }

        $this->subscriberListManager->delete($list);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('', name: 'create_list', methods: ['POST'])]
    #[OA\Post(
        path: '/lists',
        description: 'Returns created list.',
        summary: 'Create a subscriber list.',
        requestBody: new OA\RequestBody(
            description: 'Pass parameters to create a new subscriber list.',
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', format: 'string', example: 'News'),
                    new OA\Property(property: 'description', type: 'string', example: 'News (and some fun stuff)'),
                    new OA\Property(property: 'list_position', type: 'number', example: 12),
                    new OA\Property(property: 'public', type: 'boolean', example: true),
                ]
            )
        ),
        tags: ['lists'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID obtained from authentication',
                in: 'header',
                required: true,
                schema: new OA\Schema(
                    type: 'string'
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/SubscriberList')
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function createList(Request $request, SubscriberListNormalizer $normalizer): JsonResponse
    {
        $authUser = $this->requireAuthentication($request);

        /** @var CreateSubscriberListRequest $subscriberListRequest */
        $subscriberListRequest = $this->validator->validate($request, CreateSubscriberListRequest::class);
        $data = $this->subscriberListManager->createSubscriberList($subscriberListRequest, $authUser);

        return new JsonResponse($normalizer->normalize($data), Response::HTTP_CREATED);
    }
}
