<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Model\Subscription\SubscriberList;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Controller\Traits\AuthenticationTrait;
use PhpList\RestBundle\Entity\Request\CreateSubscriberListRequest;
use PhpList\RestBundle\Serializer\SubscriberListNormalizer;
use PhpList\RestBundle\Service\Manager\SubscriberListManager;
use PhpList\RestBundle\Validator\RequestValidator;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
#[Route('/lists')]
class ListController extends AbstractController
{
    use AuthenticationTrait;

    private SubscriberListNormalizer $normalizer;
    private SubscriberListManager $subscriberListManager;
    private RequestValidator $validator;

    public function __construct(
        Authentication $authentication,
        SubscriberListNormalizer $normalizer,
        RequestValidator $validator,
        SubscriberListManager $subscriberListManager
    ) {
        $this->authentication = $authentication;
        $this->normalizer = $normalizer;
        $this->validator = $validator;
        $this->subscriberListManager = $subscriberListManager;
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
                            new OA\Property(property: 'name', type: 'string', example: 'News'),
                            new OA\Property(
                                property: 'description',
                                type: 'string',
                                example: 'News (and some fun stuff)'
                            ),
                            new OA\Property(
                                property: 'creation_date',
                                type: 'string',
                                format: 'date-time',
                                example: '2016-06-22T15:01:17+00:00'
                            ),
                            new OA\Property(property: 'list_position', type: 'integer', example: 12),
                            new OA\Property(property: 'subject_prefix', type: 'string', example: 'phpList'),
                            new OA\Property(property: 'public', type: 'boolean', example: true),
                            new OA\Property(property: 'category', type: 'string', example: 'news'),
                            new OA\Property(property: 'id', type: 'integer', example: 1)
                        ],
                        type: 'object'
                    )
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
        $data = $this->subscriberListManager->getAll();

        $normalized = array_map(function ($item) {
            return $this->normalizer->normalize($item);
        }, $data);

        return new JsonResponse($normalized, Response::HTTP_OK);
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
                content: new OA\JsonContent(
                    type: 'object',
                    example: [
                        'name' => 'News',
                        'description' => 'News (and some fun stuff)',
                        'creation_date' => '2016-06-22T15:01:17+00:00',
                        'list_position' => 12,
                        'subject_prefix' => 'phpList',
                        'public' => true,
                        'category' => 'news',
                        'id' => 1
                    ]
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
        #[MapEntity(mapping: ['listId' => 'id'])] SubscriberList $list
    ): JsonResponse {
        $this->requireAuthentication($request);

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
        #[MapEntity(mapping: ['listId' => 'id'])] SubscriberList $list
    ): JsonResponse {
        $this->requireAuthentication($request);

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
                content: new OA\JsonContent(
                    type: 'object',
                    example: [
                        'name' => 'News',
                        'description' => 'News (and some fun stuff)',
                        'creation_date' => '2016-06-22T15:01:17+00:00',
                        'list_position' => 12,
                        'subject_prefix' => 'phpList',
                        'public' => true,
                        'category' => 'news',
                        'id' => 1
                    ]
                )
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
