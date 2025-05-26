<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Subscription\Model\SubscriberList;
use PhpList\Core\Domain\Subscription\Service\Manager\SubscriberListManager;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Service\Provider\PaginatedDataProvider;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Subscription\Request\CreateSubscriberListRequest;
use PhpList\RestBundle\Subscription\Serializer\SubscriberListNormalizer;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This controller provides REST API access to subscriber lists.
 *
 * @author Oliver Klee <oliver@phplist.com>
 * @author Xheni Myrtaj <xheni@phplist.com>
 * @author Tatevik Grigoryan <tatevik@phplist.com>
 */
#[Route('/lists', name: 'subscriber_list_')]
class SubscriberListController extends BaseController
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

    #[Route('', name: 'get_list', methods: ['GET'])]
    #[OA\Get(
        path: '/lists',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. Returns a JSON list of all subscriber lists.',
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

        return $this->json(
            $this->paginatedDataProvider->getPaginatedList($request, $this->normalizer, SubscriberList::class),
            Response::HTTP_OK
        );
    }

    #[Route('/{listId}', name: 'get_one', requirements: ['listId' => '\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/lists/{listId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. Returns a single subscriber list with specified ID.',
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
            throw $this->createNotFoundException('Subscriber list not found.');
        }

        return $this->json($this->normalizer->normalize($list), Response::HTTP_OK);
    }

    #[Route('/{listId}', name: 'delete', requirements: ['listId' => '\d+'], methods: ['DELETE'])]
    #[OA\Delete(
        path: '/lists/{listId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. Deletes a single subscriber list.',
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
            throw $this->createNotFoundException('Subscriber list not found.');
        }

        $this->subscriberListManager->delete($list);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/lists',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. Returns created list.',
        summary: 'Create a subscriber list.',
        requestBody: new OA\RequestBody(
            description: 'Pass parameters to create a new subscriber list.',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CreateSubscriberListRequest')
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
        $data = $this->subscriberListManager->createSubscriberList($subscriberListRequest->getDto(), $authUser);

        return $this->json($normalizer->normalize($data), Response::HTTP_CREATED);
    }
}
