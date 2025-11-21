<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Controller;

use Doctrine\ORM\EntityManagerInterface;
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
use PhpList\RestBundle\Subscription\Service\SubscriberHistoryService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        private readonly SubscriberManager $subscriberManager,
        private readonly SubscriberNormalizer $subscriberNormalizer,
        private readonly SubscriberHistoryService $subscriberHistoryService,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct($authentication, $validator);
        $this->authentication = $authentication;
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
        $this->entityManager->flush();
        $subscriberData = $this->subscriberNormalizer->normalize($subscriber, 'json');

        return $this->json($subscriberData, Response::HTTP_CREATED);
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
        $subscriber = $this->subscriberManager->updateSubscriber(
            subscriber: $subscriber,
            subscriberDto: $updateSubscriberRequest->getDto(),
            admin: $admin
        );
        $this->entityManager->flush();
        $subscriberData = $this->subscriberNormalizer->normalize($subscriber, 'json');

        return $this->json($subscriberData, Response::HTTP_OK);
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

        $subscriber = $this->subscriberManager->getSubscriberById($subscriberId);
        $subscriberData = $this->subscriberNormalizer->normalize($subscriber);

        return $this->json($subscriberData, Response::HTTP_OK);
    }

    #[Route('/{subscriberId}/history', name: 'history', requirements: ['subscriberId' => '\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/subscribers/{subscriberId}/history',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ',
        summary: 'Get subscriber event history',
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
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'after_id',
                description: 'Page number (pagination)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Max items per page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 25)
            ),
            new OA\Parameter(
                name: 'ip',
                description: 'Filter by IP address',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'date_from',
                description: 'Filter by date (format: Y-m-d)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'summery',
                description: 'Filter by summary text',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of subscriber events',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/SubscriberHistory')
                        ),
                        new OA\Property(property: 'pagination', ref: '#/components/schemas/CursorPagination')
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundErrorResponse')
            )
        ]
    )]
    public function getSubscriberHistory(
        Request $request,
        #[MapEntity(mapping: ['subscriberId' => 'id'])] ?Subscriber $subscriber = null,
    ): JsonResponse {
        $this->requireAuthentication($request);

        $historyData = $this->subscriberHistoryService->getSubscriberHistory($request, $subscriber);

        return $this->json(
            data: $historyData,
            status: Response::HTTP_OK,
        );
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
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route(
        '/{subscriberId}/reset-bounce-count',
        name: 'reset_bounce_count',
        requirements: ['subscriberId' => '\d+'],
        methods: ['POST']
    )]
    #[OA\Post(
        path: '/api/v2/subscribers/{subscriberId}/reset-bounce-count',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production.',
        summary: 'Reset bounce count for a subscriber.',
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
    public function resetBounceCount(
        Request $request,
        #[MapEntity(mapping: ['subscriberId' => 'id'])] ?Subscriber $subscriber = null,
    ): Response {
        $admin = $this->requireAuthentication($request);
        if (!$admin->getPrivileges()->has(PrivilegeFlag::Subscribers)) {
            throw $this->createAccessDeniedException('You are not allowed to manage Subscribers.');
        }

        if (!$subscriber) {
            throw $this->createNotFoundException('Subscriber not found.');
        }

        $subscriber = $this->subscriberManager->resetBounceCount($subscriber);
        $this->entityManager->flush();
        $subscriberData = $this->subscriberNormalizer->normalize($subscriber, 'json');

        return $this->json($subscriberData, Response::HTTP_OK);
    }

    #[Route('/confirm', name: 'confirm', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/subscribers/confirm',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production.',
        summary: 'Confirm a subscriber by uniqueId.',
        tags: ['subscribers'],
        parameters: [
            new OA\Parameter(
                name: 'uniqueId',
                description: 'Unique identifier for the subscriber confirmation',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'e9d8c9b2e6')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Subscriber confirmed',
                content: new OA\MediaType(
                    mediaType: 'text/html'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Missing or invalid uniqueId'
            ),
            new OA\Response(
                response: 404,
                description: 'Subscriber not found'
            )
        ]
    )]
    public function setSubscriberAsConfirmed(Request $request): Response
    {
        $uniqueId = $request->query->get('uniqueId');

        if (!$uniqueId) {
            return new Response('<h1>Missing confirmation code.</h1>', 400);
        }

        try {
            $this->subscriberManager->markAsConfirmedByUniqueId($uniqueId);
            $this->entityManager->flush();
        } catch (NotFoundHttpException) {
            return new Response('<h1>Subscriber isn\'t found or already confirmed.</h1>', 404);
        }

        return new Response('<h1>Thank you, your subscription is confirmed!</h1>');
    }
}
