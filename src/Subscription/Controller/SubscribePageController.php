<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Identity\Model\PrivilegeFlag;
use PhpList\Core\Domain\Subscription\Service\Manager\SubscribePageManager;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Validator\RequestValidator;
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
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'title', type: 'string'),
                        new OA\Property(property: 'active', type: 'boolean'),
                        new OA\Property(property: 'owner_id', type: 'integer', nullable: true),
                    ]
                ),
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
    public function getPage(Request $request, int $id): JsonResponse
    {
        $admin = $this->requireAuthentication($request);
        if (!$admin->getPrivileges()->has(PrivilegeFlag::Subscribers)) {
            throw $this->createAccessDeniedException('You are not allowed to view subscribe pages.');
        }

        $page = $this->subscribePageManager->getPage($id);

        return $this->json([
            'id' => $page->getId(),
            'title' => $page->getTitle(),
            'active' => $page->isActive(),
            'owner_id' => $page->getOwner()?->getId(),
        ], Response::HTTP_OK);
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
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'title', type: 'string'),
                        new OA\Property(property: 'active', type: 'boolean'),
                        new OA\Property(property: 'owner_id', type: 'integer', nullable: true),
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

        $data = json_decode($request->getContent(), true) ?: [];
        $title = isset($data['title']) ? trim((string)$data['title']) : '';
        $active = isset($data['active']) ? (bool)$data['active'] : false;

        if ($title === '') {
            return $this->json([
                'errors' => [
                    ['field' => 'title', 'message' => 'This field is required.']
                ]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $page = $this->subscribePageManager->createPage($title, $active, $admin);

        return $this->json([
            'id' => $page->getId(),
            'title' => $page->getTitle(),
            'active' => $page->isActive(),
            'owner_id' => $page->getOwner()?->getId(),
        ], Response::HTTP_CREATED);
    }
}
