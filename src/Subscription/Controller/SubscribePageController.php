<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Controller;

use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Common\Model\Filter\PaginatedFilter;
use PhpList\Core\Domain\Identity\Model\PrivilegeFlag;
use PhpList\Core\Domain\Subscription\Model\SubscribePage;
use PhpList\Core\Domain\Subscription\Service\Manager\SubscribePageManager;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Service\Provider\PaginatedDataProvider;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Subscription\Request\SubscribePageRequest;
use PhpList\RestBundle\Subscription\Serializer\SubscribePageNormalizer;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/subscribe-pages', name: 'subscribe_pages_')]
class SubscribePageController extends BaseController
{
    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        private readonly SubscribePageManager $subscribePageManager,
        private readonly SubscribePageNormalizer $normalizer,
        private readonly EntityManagerInterface $entityManager,
        private readonly PaginatedDataProvider $paginatedProvider,
    ) {
        parent::__construct($authentication, $validator);
    }

    #[Route('/', name: 'get_all', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/subscribe-pages',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production.',
        summary: 'Get subscribe pages list',
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
                            items: new OA\Items(ref: '#/components/schemas/SubscribePage')
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
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundErrorResponse')
            ),
        ]
    )]
    public function getPages(Request $request): JsonResponse
    {
        $admin = $this->requireAuthentication($request);
        if (!$admin->getPrivileges()->has(PrivilegeFlag::Subscribers)) {
            throw $this->createAccessDeniedException('You are not allowed to view subscribe pages.');
        }

        return $this->json(
            $this->paginatedProvider->getPaginatedList(
                request: $request,
                normalizer: $this->normalizer,
                className: SubscribePage::class,
                filter: new PaginatedFilter(),
            ),
            Response::HTTP_OK
        );
    }

    #[Route('/{id}', name: 'get', requirements: ['id' => '\\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/subscribe-pages/{id}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production.',
        summary: 'Get subscribe page',
        tags: ['subscriptions'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'id',
                description: 'Subscribe page ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/SubscribePage'),
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundErrorResponse')
            ),
        ]
    )]
    public function getPage(Request $request): JsonResponse
    {
        $admin = $this->authentication->authenticateByApiKey($request);
        $page = $this->subscribePageManager->findPage(id: (int) $request->get('id'));

        if (!$page || ($page->isActive() === false && $admin === null)) {
            throw $this->createNotFoundException('Subscribe page not found');
        }

        return $this->json($this->normalizer->normalize($page), Response::HTTP_OK);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v2/subscribe-pages',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production.',
        summary: 'Create subscribe page',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'active', type: 'boolean', nullable: true),
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'key', type: 'string'),
                                new OA\Property(property: 'value', type: 'string'),
                            ],
                            type: 'object'
                        ),
                        nullable: true
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
            )
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Created',
                content: new OA\JsonContent(ref: '#/components/schemas/SubscribePage')
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            )
        ]
    )]
    public function createPage(Request $request): JsonResponse
    {
        $admin = $this->requireAuthentication($request);
        if (!$admin->getPrivileges()->has(PrivilegeFlag::Subscribers)) {
            throw $this->createAccessDeniedException('You are not allowed to create subscribe pages.');
        }

        /** @var SubscribePageRequest $createRequest */
        $createRequest = $this->validator->validate($request, SubscribePageRequest::class);

        $page = $this->subscribePageManager->createPage($createRequest->title, $createRequest->active, $admin);
        if ($createRequest->hasData()) {
            $this->entityManager->flush();
            $this->subscribePageManager->syncPageData($createRequest->getDataMap(), $page);
        }
        $this->entityManager->flush();

        return $this->json($this->normalizer->normalize($page), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\\d+'], methods: ['PUT'])]
    #[OA\Put(
        path: '/api/v2/subscribe-pages/{id}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production.',
        summary: 'Update subscribe page',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', nullable: true),
                    new OA\Property(property: 'active', type: 'boolean', nullable: true),
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'key', type: 'string'),
                                new OA\Property(property: 'value', type: 'string'),
                            ],
                            type: 'object'
                        ),
                        nullable: true
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
                name: 'id',
                description: 'Subscribe page ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/SubscribePage')
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundErrorResponse')
            ),
        ]
    )]
    public function updatePage(
        Request $request,
        #[MapEntity(mapping: ['id' => 'id'])] ?SubscribePage $page = null
    ): JsonResponse {
        $admin = $this->requireAuthentication($request);
        if (!$admin->getPrivileges()->has(PrivilegeFlag::Subscribers)) {
            throw $this->createAccessDeniedException('You are not allowed to update subscribe pages.');
        }

        if (!$page) {
            throw $this->createNotFoundException('Subscribe page not found');
        }

        /** @var SubscribePageRequest $updateRequest */
        $updateRequest = $this->validator->validate($request, SubscribePageRequest::class);

        $updated = $this->subscribePageManager->updatePage(
            page: $page,
            title: $updateRequest->title,
            active: $updateRequest->active,
            owner: $admin,
        );
        if ($updateRequest->hasData()) {
            $this->subscribePageManager->syncPageData(data: $updateRequest->getDataMap(), page: $page);
        }
        $this->entityManager->flush();

        return $this->json($this->normalizer->normalize($updated), Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\\d+'], methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/v2/subscribe-pages/{id}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production.',
        summary: 'Delete subscribe page',
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
                name: 'id',
                description: 'Subscribe page ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(response: 204, description: 'No Content'),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundErrorResponse')
            )
        ]
    )]
    public function deletePage(
        Request $request,
        #[MapEntity(mapping: ['id' => 'id'])] ?SubscribePage $page = null
    ): JsonResponse {
        $admin = $this->requireAuthentication($request);
        if (!$admin->getPrivileges()->has(PrivilegeFlag::Subscribers)) {
            throw $this->createAccessDeniedException('You are not allowed to delete subscribe pages.');
        }

        if ($page === null) {
            throw $this->createNotFoundException('Subscribe page not found');
        }

        $this->subscribePageManager->deletePage($page);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
