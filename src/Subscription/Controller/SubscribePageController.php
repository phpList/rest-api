<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Controller;

use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Identity\Model\PrivilegeFlag;
use PhpList\Core\Domain\Subscription\Model\SubscribePage;
use PhpList\Core\Domain\Subscription\Service\Manager\SubscribePageManager;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Subscription\Request\SubscribePageDataRequest;
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
    ) {
        parent::__construct($authentication, $validator);
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
    public function getPage(
        Request $request,
        #[MapEntity(mapping: ['id' => 'id'])] ?SubscribePage $page = null
    ): JsonResponse {
        $admin = $this->requireAuthentication($request);
        if (!$admin->getPrivileges()->has(PrivilegeFlag::Subscribers)) {
            throw $this->createAccessDeniedException('You are not allowed to view subscribe pages.');
        }

        if (!$page) {
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

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/data', name: 'get_data', requirements: ['id' => '\\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/subscribe-pages/{id}/data',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production.',
        summary: 'Get subscribe page data',
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
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'name', type: 'string'),
                            new OA\Property(property: 'data', type: 'string', nullable: true),
                        ],
                        type: 'object'
                    )
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
            )
        ]
    )]
    public function getPageData(
        Request $request,
        #[MapEntity(mapping: ['id' => 'id'])] ?SubscribePage $page = null
    ): JsonResponse {
        $admin = $this->requireAuthentication($request);
        if (!$admin->getPrivileges()->has(PrivilegeFlag::Subscribers)) {
            throw $this->createAccessDeniedException('You are not allowed to view subscribe page data.');
        }

        if (!$page) {
            throw $this->createNotFoundException('Subscribe page not found');
        }

        $data = $this->subscribePageManager->getPageData($page);

        $json = array_map(static function ($item) {
            return [
                'id' => $item->getId(),
                'name' => $item->getName(),
                'data' => $item->getData(),
            ];
        }, $data);

        return $this->json($json, Response::HTTP_OK);
    }

    #[Route('/{id}/data', name: 'set_data', requirements: ['id' => '\\d+'], methods: ['PUT'])]
    #[OA\Put(
        path: '/api/v2/subscribe-pages/{id}/data',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production.',
        summary: 'Set subscribe page data item',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'value', type: 'string', nullable: true),
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
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'data', type: 'string', nullable: true),
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
            )
        ]
    )]
    public function setPageData(
        Request $request,
        #[MapEntity(mapping: ['id' => 'id'])] ?SubscribePage $page = null
    ): JsonResponse {
        $admin = $this->requireAuthentication($request);
        if (!$admin->getPrivileges()->has(PrivilegeFlag::Subscribers)) {
            throw $this->createAccessDeniedException('You are not allowed to update subscribe page data.');
        }

        if (!$page) {
            throw $this->createNotFoundException('Subscribe page not found');
        }

        /** @var SubscribePageDataRequest $createRequest */
        $createRequest = $this->validator->validate($request, SubscribePageDataRequest::class);

        $item = $this->subscribePageManager->setPageData($page, $createRequest->name, $createRequest->value);

        return $this->json([
            'id' => $item->getId(),
            'name' => $item->getName(),
            'data' => $item->getData(),
        ], Response::HTTP_OK);
    }
}
