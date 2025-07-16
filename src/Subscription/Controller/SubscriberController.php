<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Identity\Model\PrivilegeFlag;
use PhpList\Core\Domain\Subscription\Model\Subscriber;
use PhpList\Core\Domain\Subscription\Service\Manager\SubscriberManager;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Subscription\Request\CreateSubscriberRequest;
use PhpList\RestBundle\Subscription\Request\UpdateSubscriberRequest;
use PhpList\RestBundle\Subscription\Serializer\SubscriberNormalizer;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This controller provides REST API access to subscribers.
 *
 * @author Oliver Klee <oliver@phplist.com>
 * @author Tatevik Grigoryan <tatevik@phplist.com>
 */
#[Route('/subscribers', name: 'subscriber_')]
class SubscriberController extends BaseController
{
    private SubscriberManager $subscriberManager;
    private SubscriberNormalizer $subscriberNormalizer;

    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        SubscriberManager $subscriberManager,
        SubscriberNormalizer $subscriberNormalizer,
    ) {
        parent::__construct($authentication, $validator);
        $this->authentication = $authentication;
        $this->subscriberManager = $subscriberManager;
        $this->subscriberNormalizer = $subscriberNormalizer;
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v2/subscribers',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Creates a new subscriber (if there is no subscriber with the given email address yet).',
        summary: 'Create a subscriber',
        requestBody: new OA\RequestBody(
            description: 'Pass session credentials',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CreateSubscriberRequest')
        ),
        tags: ['subscribers'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
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
    public function createSubscriber(Request $request): JsonResponse
    {
        $admin = $this->requireAuthentication($request);
        if (!$admin->getPrivileges()->has(PrivilegeFlag::Subscribers)) {
            throw $this->createAccessDeniedException('You are not allowed to create subscribers.');
        }

        /** @var CreateSubscriberRequest $subscriberRequest */
        $subscriberRequest = $this->validator->validate($request, CreateSubscriberRequest::class);
        $subscriber = $this->subscriberManager->createSubscriber($subscriberRequest->getDto());

        return $this->json(
            $this->subscriberNormalizer->normalize($subscriber, 'json'),
            Response::HTTP_CREATED
        );
    }

    #[Route('/{subscriberId}', name: 'update', requirements: ['subscriberId' => '\d+'], methods: ['PUT'])]
    #[OA\Put(
        path: '/api/v2/subscribers/{subscriberId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Update subscriber data by id.',
        summary: 'Update subscriber',
        requestBody: new OA\RequestBody(
            description: 'Pass session credentials',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateSubscriberRequest')
        ),
        tags: ['subscribers'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
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
        #[MapEntity(mapping: ['subscriberId' => 'id'])] ?Subscriber $subscriber = null,
    ): JsonResponse {
        $admin = $this->requireAuthentication($request);
        if (!$admin->getPrivileges()->has(PrivilegeFlag::Subscribers)) {
            throw $this->createAccessDeniedException('You are not allowed to update subscribers.');
        }

        if (!$subscriber) {
            throw $this->createNotFoundException('Subscriber not found.');
        }
        /** @var UpdateSubscriberRequest $updateSubscriberRequest */
        $updateSubscriberRequest = $this->validator->validate($request, UpdateSubscriberRequest::class);
        $subscriber = $this->subscriberManager->updateSubscriber($updateSubscriberRequest->getDto());

        return $this->json($this->subscriberNormalizer->normalize($subscriber, 'json'), Response::HTTP_OK);
    }

    #[Route('/{subscriberId}', name: 'get_one', requirements: ['subscriberId' => '\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/subscribers/{subscriberId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Get subscriber data by id.',
        summary: 'Get a subscriber',
        tags: ['subscribers'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
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
    public function getSubscriber(Request $request, int $subscriberId): JsonResponse
    {
        $this->requireAuthentication($request);

        $subscriber = $this->subscriberManager->getSubscriber($subscriberId);

        return $this->json($this->subscriberNormalizer->normalize($subscriber), Response::HTTP_OK);
    }

    #[Route('/{subscriberId}', name: 'delete', requirements: ['subscriberId' => '\d+'], methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/v2/subscribers/{subscriberId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Delete subscriber by id.',
        summary: 'Delete subscriber',
        tags: ['subscribers'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
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
        $admin = $this->requireAuthentication($request);
        if (!$admin->getPrivileges()->has(PrivilegeFlag::Subscribers)) {
            throw $this->createAccessDeniedException('You are not allowed to delete subscribers.');
        }

        if (!$subscriber) {
            throw $this->createNotFoundException('Subscriber not found.');
        }
        $this->subscriberManager->deleteSubscriber($subscriber);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
