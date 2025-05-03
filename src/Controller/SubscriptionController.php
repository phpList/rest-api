<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Model\Subscription\SubscriberList;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Entity\Request\SubscriptionRequest;
use PhpList\RestBundle\Serializer\SubscriberNormalizer;
use PhpList\RestBundle\Serializer\SubscriptionNormalizer;
use PhpList\RestBundle\Service\Manager\SubscriptionManager;
use PhpList\RestBundle\Validator\RequestValidator;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This controller provides REST API access to subscriptions.
 *
 * @author Tatevik Grigoryan <tatevik@phplist.com>
 */
#[Route('/lists')]
class SubscriptionController extends BaseController
{
    private SubscriptionManager $subscriptionManager;
    private SubscriberNormalizer $subscriberNormalizer;
    private SubscriptionNormalizer $subscriptionNormalizer;

    public function __construct(
        Authentication $authentication,
        SubscriptionManager $subscriptionManager,
        RequestValidator $validator,
        SubscriberNormalizer $subscriberNormalizer,
        SubscriptionNormalizer $subscriptionNormalizer,
    ) {
        parent::__construct($authentication, $validator);
        $this->subscriptionManager = $subscriptionManager;
        $this->subscriberNormalizer = $subscriberNormalizer;
        $this->subscriptionNormalizer = $subscriptionNormalizer;
    }

    #[Route('/{listId}/subscribers', name: 'get_subscriber_from_list', methods: ['GET'])]
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
            throw new NotFoundHttpException('Subscriber list not found.');
        }

        $subscribers = $this->subscriptionManager->getSubscriberListMembers($list);
        $normalized = array_map(fn($subscriber) => $this->subscriberNormalizer->normalize($subscriber), $subscribers);

        return new JsonResponse($normalized, Response::HTTP_OK);
    }

    #[Route('/{listId}/subscribers/count', name: 'get_subscribers_count_from_list', methods: ['GET'])]
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
            throw new NotFoundHttpException('Subscriber list not found.');
        }

        return new JsonResponse(['subscribers_count' => count($list->getSubscribers())], Response::HTTP_OK);
    }

    #[Route('/{listId}/subscribers', name: 'create_subscription', methods: ['POST'])]
    #[OA\Post(
        path: '/lists/{listId}/subscribers',
        description: 'Subscribe subscriber to a list.',
        summary: 'Create subscription',
        requestBody: new OA\RequestBody(
            description: 'Pass session credentials',
            required: true,
            content: new OA\JsonContent(
                required: ['emails'],
                properties: [
                    new OA\Property(
                        property: 'emails',
                        type: 'array',
                        items: new OA\Items(type: 'string', format: 'email'),
                        example: ['test1@example.com', 'test2@example.com']
                    ),
                ]
            )
        ),
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
            ),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Subscription')
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/BadRequestResponse')
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
            ),
            new OA\Response(
                response: 409,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/AlreadyExistsResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function createSubscription(
        Request $request,
        #[MapEntity(mapping: ['listId' => 'id'])] ?SubscriberList $list = null,
    ): JsonResponse {
        $this->requireAuthentication($request);

        if (!$list) {
            throw new NotFoundHttpException('Subscriber list not found.');
        }

        /** @var SubscriptionRequest $subscriptionRequest */
        $subscriptionRequest = $this->validator->validate($request, SubscriptionRequest::class);
        $subscriptions = $this->subscriptionManager->createSubscriptions($list, $subscriptionRequest->emails);
        $normalized = array_map(fn($item) => $this->subscriptionNormalizer->normalize($item), $subscriptions);

        return new JsonResponse($normalized, Response::HTTP_CREATED);
    }

    #[Route('/{listId}/subscribers', name: 'delete_subscription', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/lists/{listId}/subscribers',
        description: 'Delete subscription.',
        summary: 'Delete subscription',
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
            ),
            new OA\Parameter(
                name: 'emails',
                description: 'emails of subscribers to delete from list.',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Success',
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
    public function deleteSubscriptions(
        Request $request,
        #[MapEntity(mapping: ['listId' => 'id'])] ?SubscriberList $list = null,
    ): JsonResponse {
        $this->requireAuthentication($request);
        if (!$list) {
            throw new NotFoundHttpException('Subscriber list not found.');
        }
        $subscriptionRequest = new SubscriptionRequest();
        $subscriptionRequest->emails = $request->query->all('emails');

        /** @var SubscriptionRequest $subscriptionRequest */
        $subscriptionRequest = $this->validator->validateDto($subscriptionRequest);
        $this->subscriptionManager->deleteSubscriptions($list, $subscriptionRequest->emails);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
