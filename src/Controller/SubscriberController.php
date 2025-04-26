<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Model\Subscription\Subscriber;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Controller\Traits\AuthenticationTrait;
use PhpList\RestBundle\Entity\Request\CreateSubscriberRequest;
use PhpList\RestBundle\Entity\Request\UpdateSubscriberRequest;
use PhpList\RestBundle\Serializer\SubscriberNormalizer;
use PhpList\RestBundle\Service\Manager\SubscriberManager;
use PhpList\RestBundle\Validator\RequestValidator;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * This controller provides REST API access to subscribers.
 *
 * @author Oliver Klee <oliver@phplist.com>
 * @author Tatevik Grigoryan <tatevik@phplist.com>
 */
#[Route('/subscribers')]
class SubscriberController extends AbstractController
{
    use AuthenticationTrait;

    private SubscriberManager $subscriberManager;

    public function __construct(Authentication $authentication, SubscriberManager $subscriberManager)
    {
        $this->authentication = $authentication;
        $this->subscriberManager = $subscriberManager;
    }

    #[Route('', name: 'create_subscriber', methods: ['POST'])]
    #[OA\Post(
        path: '/subscribers',
        description: 'Creates a new subscriber (if there is no subscriber with the given email address yet).',
        summary: 'Create a subscriber',
        requestBody: new OA\RequestBody(
            description: 'Pass session credentials',
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'string', example: 'admin@example.com'),
                    new OA\Property(property: 'request_confirmation', type: 'boolean', example: false),
                    new OA\Property(property: 'html_email', type: 'boolean', example: false),
                ]
            )
        ),
        tags: ['subscribers'],
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
                content: new OA\JsonContent(ref: '#/components/schemas/Subscriber'),
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
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
    public function createSubscriber(
        Request $request,
        SerializerInterface $serializer,
        RequestValidator $validator
    ): JsonResponse {
        $this->requireAuthentication($request);

        /** @var CreateSubscriberRequest $subscriberRequest */
        $subscriberRequest = $validator->validate($request, CreateSubscriberRequest::class);
        $subscriber = $this->subscriberManager->createSubscriber($subscriberRequest);

        return new JsonResponse(
            $serializer->serialize($subscriber, 'json'),
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    #[Route('/{subscriberId}', name: 'update_subscriber', requirements: ['subscriberId' => '\d+'], methods: ['PUT'])]
    #[OA\Put(
        path: '/subscribers/{subscriberId}',
        description: 'Update subscriber data by id.',
        summary: 'Update subscriber',
        requestBody: new OA\RequestBody(
            description: 'Pass session credentials',
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'string', example: 'admin@example.com'),
                    new OA\Property(property: 'confirmed', type: 'boolean', example: false),
                    new OA\Property(property: 'blacklisted', type: 'boolean', example: false),
                    new OA\Property(property: 'html_email', type: 'boolean', example: false),
                    new OA\Property(property: 'disabled', type: 'boolean', example: false),
                    new OA\Property(property: 'additional_data', type: 'string', example: 'asdf'),
                ]
            )
        ),
        tags: ['subscribers'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID obtained from authentication',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'subscriberId',
                description: 'Subscriber ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Subscriber'),
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
            new OA\Response(
                response: 404,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundErrorResponse')
            )
        ]
    )]
    public function updateSubscriber(
        Request $request,
        SerializerInterface $serializer,
        RequestValidator $validator,
        SubscriberNormalizer $subscriberNormalizer,
        #[MapEntity(mapping: ['subscriberId' => 'id'])] ?Subscriber $subscriber = null,
    ): JsonResponse {
        $this->requireAuthentication($request);

        if (!$subscriber) {
            throw new NotFoundHttpException('Subscriber not found.');
        }

        /** @var UpdateSubscriberRequest $dto */
        $dto = $serializer->deserialize($request->getContent(), UpdateSubscriberRequest::class, 'json');
        $dto->subscriberId = $subscriber->getId();
        $validator->validateDto($dto);
        $subscriber = $this->subscriberManager->updateSubscriber($dto);

        return new JsonResponse($subscriberNormalizer->normalize($subscriber, 'json'), Response::HTTP_OK);
    }

    #[Route('/{subscriberId}', name: 'get_subscriber_by_id', methods: ['GET'])]
    #[OA\Get(
        path: '/subscribers/{subscriberId}',
        description: 'Get subscriber data by id.',
        summary: 'Get a subscriber',
        tags: ['subscribers'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID obtained from authentication',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'subscriberId',
                description: 'Subscriber ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Subscriber'),
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
    public function getSubscriber(Request $request, int $subscriberId, SubscriberNormalizer $serializer): JsonResponse
    {
        $this->requireAuthentication($request);

        $subscriber = $this->subscriberManager->getSubscriber($subscriberId);

        return new JsonResponse($serializer->normalize($subscriber), Response::HTTP_OK);
    }

    #[Route('/{subscriberId}', name: 'delete_subscriber', requirements: ['subscriberId' => '\d+'], methods: ['DELETE'])]
    #[OA\Delete(
        path: '/subscribers/{subscriberId}',
        description: 'Delete subscriber by id.',
        summary: 'Delete subscriber',
        tags: ['subscribers'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID obtained from authentication',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'subscriberId',
                description: 'Subscriber ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
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
    public function deleteSubscriber(
        Request $request,
        #[MapEntity(mapping: ['subscriberId' => 'id'])] ?Subscriber $subscriber = null,
    ): JsonResponse {
        $this->requireAuthentication($request);

        if (!$subscriber) {
            throw new NotFoundHttpException('Subscriber not found.');
        }
        $this->subscriberManager->deleteSubscriber($subscriber);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
