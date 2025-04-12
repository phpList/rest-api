<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Controller\Traits\AuthenticationTrait;
use PhpList\RestBundle\Entity\Request\CreateSubscriptionRequest;
use PhpList\RestBundle\Serializer\SubscriptionNormalizer;
use PhpList\RestBundle\Service\Manager\SubscriptionManager;
use PhpList\RestBundle\Validator\RequestValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This controller provides REST API access to subscriptions.
 *
 * @author Tatevik Grigoryan <tatevik@phplist.com>
 */
#[Route('/subscriptions')]
class SubscriptionController extends AbstractController
{
    use AuthenticationTrait;

    private SubscriptionManager $subscriptionManager;
    private RequestValidator $validator;

    public function __construct(
        Authentication $authentication,
        SubscriptionManager $subscriptionManager,
        RequestValidator $validator
    ) {
        $this->authentication = $authentication;
        $this->subscriptionManager = $subscriptionManager;
        $this->validator = $validator;
    }

    #[Route('', name: 'create_subscription', methods: ['POST'])]
    #[OA\Post(
        path: '/subscriptions',
        description: 'Subscribe subscriber to a list.',
        summary: 'Create subscription',
        requestBody: new OA\RequestBody(
            description: 'Pass session credentials',
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'list_id'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'test@example.com'),
                    new OA\Property(property: 'list_id', type: 'integer', example: 2),
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
            )
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Subscription'),
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
            new OA\Response(
                response: 400,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/BadRequestResponse')
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
    public function createSubscription(Request $request, SubscriptionNormalizer $serializer): JsonResponse
    {
        $this->requireAuthentication($request);

        /** @var CreateSubscriptionRequest $subscriptionRequest */
        $subscriptionRequest = $this->validator->validate($request, CreateSubscriptionRequest::class);
        $subscription = $this->subscriptionManager->createSubscription(
            $subscriptionRequest->email,
            $subscriptionRequest->listId
        );

        return new JsonResponse($serializer->normalize($subscription, 'json'), Response::HTTP_CREATED);
    }
}
