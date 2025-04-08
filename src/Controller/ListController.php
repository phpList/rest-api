<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use PhpList\Core\Domain\Model\Messaging\SubscriberList;
use PhpList\Core\Domain\Repository\Subscription\SubscriberRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use PhpList\Core\Domain\Repository\Messaging\SubscriberListRepository;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Controller\Traits\AuthenticationTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;

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

    private SubscriberListRepository $subscriberListRepository;
    private SubscriberRepository $subscriberRepository;
    private SerializerInterface $serializer;

    public function __construct(
        Authentication $authentication,
        SubscriberListRepository $repository,
        SubscriberRepository $subscriberRepository,
        SerializerInterface $serializer
    ) {
        $this->authentication = $authentication;
        $this->subscriberListRepository = $repository;
        $this->subscriberRepository = $subscriberRepository;
        $this->serializer = $serializer;
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
        $data = $this->subscriberListRepository->findAll();
        $json = $this->serializer->serialize($data, 'json', [
            AbstractNormalizer::GROUPS => 'SubscriberList',
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
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
        $json = $this->serializer->serialize($list, 'json', [
            AbstractNormalizer::GROUPS => 'SubscriberList',
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
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
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'There is no session with that ID.'
                        )
                    ],
                    type: 'object'
                )
            )
        ]
    )]
    public function deleteList(
        Request $request,
        #[MapEntity(mapping: ['listId' => 'id'])] SubscriberList $list
    ): JsonResponse {
        $this->requireAuthentication($request);

        $this->subscriberListRepository->remove($list);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT, [], false);
    }

    #[Route('/{listId}/subscribers', name: 'get_subscriber_from_list', methods: ['GET'])]
    #[OA\Get(
        path: '/lists/{listId}/subscribers',
        description: 'Returns a JSON list of all subscribers for a subscriber list.',
        summary: 'Gets a list of all subscribers of a subscriber list.',
        tags: ['lists'],
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
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Subscriber')
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            )
        ]
    )]
    public function getListMembers(
        Request $request,
        #[MapEntity(mapping: ['listId' => 'id'])] SubscriberList $list
    ): JsonResponse {
        $this->requireAuthentication($request);

        $subscribers = $this->subscriberRepository->getSubscribersBySubscribedListId($list->getId());

        $json = $this->serializer->serialize($subscribers, 'json', [
            AbstractNormalizer::GROUPS => 'SubscriberListMembers',
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{listId}/subscribers/count', name: 'get_subscribers_count_from_list', methods: ['GET'])]
    #[OA\Get(
        path: '/lists/{listId}/count',
        description: 'Returns a count of all subscribers in a given list.',
        summary: 'Gets the total number of subscribers of a list',
        tags: ['lists'],
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
                description: 'Success'
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
        #[MapEntity(mapping: ['listId' => 'id'])] SubscriberList $list
    ): JsonResponse {
        $this->requireAuthentication($request);
        $json = $this->serializer->serialize(count($list->getSubscribers()), 'json');

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
