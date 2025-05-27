<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Identity\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Identity\Model\Administrator;
use PhpList\Core\Domain\Identity\Service\AdministratorManager;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Service\Provider\PaginatedDataProvider;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Identity\Request\CreateAdministratorRequest;
use PhpList\RestBundle\Identity\Request\UpdateAdministratorRequest;
use PhpList\RestBundle\Identity\Serializer\AdministratorNormalizer;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This controller provides CRUD operations for Administrator entities.
 */
#[Route('/administrators', name: 'admin_')]
class AdministratorController extends BaseController
{
    private AdministratorManager $administratorManager;
    private AdministratorNormalizer $normalizer;
    private PaginatedDataProvider $paginatedProvider;

    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        AdministratorManager $administratorManager,
        AdministratorNormalizer $normalizer,
        PaginatedDataProvider $paginatedProvider
    ) {
        parent::__construct($authentication, $validator);
        $this->administratorManager = $administratorManager;
        $this->normalizer = $normalizer;
        $this->paginatedProvider = $paginatedProvider;
    }

    #[Route('', name: 'get_list', methods: ['GET'])]
    #[OA\Get(
        path: '/administrators',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Get list of administrators.',
        summary: 'Get Administrators',
        tags: ['administrators'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID obtained from authentication',
                in: 'header',
                required: true,
                schema: new OA\Schema(
                    type: 'string'
                )
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
                            items: new OA\Items(ref: '#/components/schemas/Administrator')
                        ),
                        new OA\Property(property: 'pagination', ref: '#/components/schemas/CursorPagination')
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid input'
            )
        ]
    )]
    public function getAdministrators(Request $request): JsonResponse
    {
        $this->requireAuthentication($request);

        return $this->json(
            $this->paginatedProvider->getPaginatedList($request, $this->normalizer, Administrator::class),
            Response::HTTP_OK
        );
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/administrators',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Create a new administrator.',
        summary: 'Create Administrator',
        requestBody: new OA\RequestBody(
            description: 'Administrator data',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CreateAdministratorRequest')
        ),
        tags: ['administrators'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Administrator created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/Administrator')
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid input'
            )
        ]
    )]
    public function createAdministrator(
        Request $request,
        RequestValidator $validator,
        AdministratorNormalizer $normalizer
    ): JsonResponse {
        $this->requireAuthentication($request);

        /** @var CreateAdministratorRequest $createRequest */
        $createRequest = $validator->validate($request, CreateAdministratorRequest::class);

        $administrator = $this->administratorManager->createAdministrator($createRequest->getDto());
        $json = $normalizer->normalize($administrator, 'json');

        return $this->json($json, Response::HTTP_CREATED);
    }

    #[Route('/{administratorId}', name: 'get_one', requirements: ['administratorId' => '\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/administrators/{administratorId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Get administrator by ID.',
        summary: 'Get Administrator',
        tags: ['administrators'],
        parameters: [
            new OA\Parameter(
                name: 'administratorId',
                description: 'Administrator ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Administrator found',
                content: new OA\JsonContent(ref: '#/components/schemas/Administrator')
            ),
            new OA\Response(
                response: 404,
                description: 'Administrator not found'
            )
        ]
    )]
    public function getAdministrator(
        Request $request,
        #[MapEntity(mapping: ['administratorId' => 'id'])] ?Administrator $administrator,
    ): JsonResponse {
        $this->requireAuthentication($request);

        if (!$administrator) {
            throw $this->createNotFoundException('Administrator not found.');
        }
        $json = $this->normalizer->normalize($administrator, 'json');

        return $this->json($json, Response::HTTP_OK);
    }

    #[Route('/{administratorId}', name: 'update', requirements: ['administratorId' => '\d+'], methods: ['PUT'])]
    #[OA\Put(
        path: '/administrators/{administratorId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Update an administrator.',
        summary: 'Update Administrator',
        requestBody: new OA\RequestBody(
            description: 'Administrator update data',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateAdministratorRequest')
        ),
        tags: ['administrators'],
        parameters: [
            new OA\Parameter(
                name: 'administratorId',
                description: 'Administrator ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Administrator updated successfully'
            ),
            new OA\Response(
                response: 404,
                description: 'Administrator not found'
            )
        ]
    )]
    public function updateAdministrator(
        Request $request,
        #[MapEntity(mapping: ['administratorId' => 'id'])] ?Administrator $administrator,
    ): JsonResponse {
        $this->requireAuthentication($request);

        if (!$administrator) {
            throw $this->createNotFoundException('Administrator not found.');
        }
        /** @var UpdateAdministratorRequest $updateRequest */
        $updateRequest = $this->validator->validate($request, UpdateAdministratorRequest::class);
        $this->administratorManager->updateAdministrator($administrator, $updateRequest->getDto());

        return $this->json($this->normalizer->normalize($administrator), Response::HTTP_OK);
    }

    #[Route('/{administratorId}', name: 'delete', requirements: ['administratorId' => '\d+'], methods: ['DELETE'])]
    #[OA\Delete(
        path: '/administrators/{administratorId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Delete an administrator.',
        summary: 'Delete Administrator',
        tags: ['administrators'],
        parameters: [
            new OA\Parameter(
                name: 'administratorId',
                description: 'Administrator ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Administrator deleted successfully'
            ),
            new OA\Response(
                response: 404,
                description: 'Administrator not found'
            )
        ]
    )]
    public function deleteAdministrator(
        Request $request,
        #[MapEntity(mapping: ['administratorId' => 'id'])] ?Administrator $administrator
    ): JsonResponse {
        $this->requireAuthentication($request);

        if (!$administrator) {
            throw $this->createNotFoundException('Administrator not found.');
        }
        $this->administratorManager->deleteAdministrator($administrator);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
