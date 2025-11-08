<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Controller;

use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Subscription\Model\SubscriberList;
use PhpList\Core\Domain\Subscription\Service\Manager\SubscriptionManager;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Subscription\Request\SubscriptionRequest;
use PhpList\RestBundle\Subscription\Serializer\SubscriptionNormalizer;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This controller provides REST API access to subscriptions.
 *
 * @author Tatevik Grigoryan <tatevik@phplist.com>
 */
#[Route('/lists', name: 'subscription_')]
class SubscriptionController extends BaseController
{
    private SubscriptionManager $subscriptionManager;
    private SubscriptionNormalizer $subscriptionNormalizer;
    private EntityManagerInterface $entityManager;

    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        SubscriptionManager $subscriptionManager,
        SubscriptionNormalizer $subscriptionNormalizer,
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($authentication, $validator);
        $this->subscriptionManager = $subscriptionManager;
        $this->subscriptionNormalizer = $subscriptionNormalizer;
        $this->entityManager = $entityManager;
    }

    #[Route('/{listId}/subscribers', name: 'create', requirements: ['listId' => '\d+'], methods: ['POST'])]
    #[OA\Post(
        path: '/api/v2/lists/{listId}/subscribers',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Subscribe subscriber to a list.',
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
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
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
            throw $this->createNotFoundException('Subscriber list not found.');
        }

        /** @var SubscriptionRequest $subscriptionRequest */
        $subscriptionRequest = $this->validator->validate($request, SubscriptionRequest::class);
        $subscriptions = $this->subscriptionManager->createSubscriptions($list, $subscriptionRequest->emails);
        $this->entityManager->flush();
        $normalized = array_map(fn($item) => $this->subscriptionNormalizer->normalize($item), $subscriptions);

        return $this->json($normalized, Response::HTTP_CREATED);
    }

    #[Route('/{listId}/subscribers', name: 'delete', requirements: ['listId' => '\d+'], methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/v2/lists/{listId}/subscribers',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Delete subscription.',
        summary: 'Delete subscription',
        tags: ['subscriptions'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
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
            throw $this->createNotFoundException('Subscriber list not found.');
        }
        $subscriptionRequest = new SubscriptionRequest();
        $subscriptionRequest->emails = $request->query->all('emails');

        /** @var SubscriptionRequest $subscriptionRequest */
        $subscriptionRequest = $this->validator->validateDto($subscriptionRequest);
        $this->subscriptionManager->deleteSubscriptions($list, $subscriptionRequest->emails);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
