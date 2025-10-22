<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Identity\Controller;

use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Identity\Model\AdminAttributeDefinition;
use PhpList\Core\Domain\Identity\Service\AdminAttributeDefinitionManager;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Service\Provider\PaginatedDataProvider;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Identity\Request\CreateAttributeDefinitionRequest;
use PhpList\RestBundle\Identity\Request\UpdateAttributeDefinitionRequest;
use PhpList\RestBundle\Identity\Serializer\AdminAttributeDefinitionNormalizer;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/administrators/attributes', name: 'admin_attribute_definition_')]
class AdminAttributeDefinitionController extends BaseController
{
    private AdminAttributeDefinitionManager $definitionManager;
    private AdminAttributeDefinitionNormalizer $normalizer;
    private PaginatedDataProvider $paginatedDataProvider;

    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        AdminAttributeDefinitionManager $definitionManager,
        AdminAttributeDefinitionNormalizer $normalizer,
        PaginatedDataProvider $paginatedDataProvider,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct($authentication, $validator);
        $this->definitionManager = $definitionManager;
        $this->normalizer = $normalizer;
        $this->paginatedDataProvider = $paginatedDataProvider;
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v2/administrators/attributes',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns created admin attribute definition.',
        summary: 'Create an admin attribute definition.',
        requestBody: new OA\RequestBody(
            description: 'Pass parameters to create admin attribute.',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CreateAdminAttributeDefinitionRequest')
        ),
        tags: ['admin-attributes'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(
                    type: 'string'
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/AdminAttributeDefinition')
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
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        $this->requireAuthentication($request);

        /** @var CreateAttributeDefinitionRequest $definitionRequest */
        $definitionRequest = $this->validator->validate($request, CreateAttributeDefinitionRequest::class);

        $attributeDefinition = $this->definitionManager->create($definitionRequest->getDto());
        $this->entityManager->flush();

        $json = $this->normalizer->normalize($attributeDefinition, 'json');

        return $this->json($json, Response::HTTP_CREATED);
    }

    #[Route('/{definitionId}', name: 'update', requirements: ['definitionId' => '\d+'], methods: ['PUT'])]
    #[OA\Put(
        path: '/api/v2/administrators/attributes/{definitionId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns updated admin attribute definition.',
        summary: 'Update an admin attribute definition.',
        requestBody: new OA\RequestBody(
            description: 'Pass parameters to update admin attribute.',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CreateAdminAttributeDefinitionRequest')
        ),
        tags: ['admin-attributes'],
        parameters: [
            new OA\Parameter(
                name: 'definitionId',
                description: 'Definition ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
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
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/AdminAttributeDefinition')
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
        ]
    )]
    public function update(
        Request $request,
        #[MapEntity(mapping: ['definitionId' => 'id'])] ?AdminAttributeDefinition $attributeDefinition,
    ): JsonResponse {
        $this->requireAuthentication($request);
        if (!$attributeDefinition) {
            throw $this->createNotFoundException('Attribute definition not found.');
        }

        /** @var UpdateAttributeDefinitionRequest $definitionRequest */
        $definitionRequest = $this->validator->validate($request, UpdateAttributeDefinitionRequest::class);

        $attributeDefinition = $this->definitionManager->update(
            attributeDefinition: $attributeDefinition,
            attributeDefinitionDto: $definitionRequest->getDto(),
        );
        $this->entityManager->flush();
        $json = $this->normalizer->normalize($attributeDefinition, 'json');

        return $this->json($json, Response::HTTP_OK);
    }

    #[Route('/{definitionId}', name: 'delete', requirements: ['definitionId' => '\d+'], methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/v2/administrators/attributes/{definitionId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Deletes a single admin attribute definition.',
        summary: 'Deletes an attribute definition.',
        tags: ['admin-attributes'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'definitionId',
                description: 'Definition ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success'
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
    public function delete(
        Request $request,
        #[MapEntity(mapping: ['definitionId' => 'id'])] ?AdminAttributeDefinition $attributeDefinition,
    ): JsonResponse {
        $this->requireAuthentication($request);
        if (!$attributeDefinition) {
            throw $this->createNotFoundException('Attribute definition not found.');
        }

        $this->definitionManager->delete($attributeDefinition);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('', name: 'get_lists', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/administrators/attributes',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns a JSON list of all admin attribute definitions.',
        summary: 'Gets a list of all admin attribute definitions.',
        tags: ['admin-attributes'],
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
                            items: new OA\Items(ref: '#/components/schemas/AdminAttributeDefinition')
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
            )
        ]
    )]
    public function getPaginated(Request $request): JsonResponse
    {
        $this->requireAuthentication($request);

        return $this->json(
            $this->paginatedDataProvider->getPaginatedList(
                $request,
                $this->normalizer,
                AdminAttributeDefinition::class,
            ),
            Response::HTTP_OK
        );
    }

    #[Route('/{definitionId}', name: 'get_one', requirements: ['definitionId' => '\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/administrators/attributes/{definitionId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns a single attribute with specified ID.',
        summary: 'Gets attribute with specified ID.',
        tags: ['admin-attributes'],
        parameters: [
            new OA\Parameter(
                name: 'definitionId',
                description: 'Definition ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
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
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/AdminAttributeDefinition')
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Failure',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'There is no attribute with that ID.'
                        )
                    ],
                    type: 'object'
                )
            )
        ]
    )]
    public function getAttributeDefinition(
        Request $request,
        #[MapEntity(mapping: ['definitionId' => 'id'])] ?AdminAttributeDefinition $attributeDefinition,
    ): JsonResponse {
        $this->requireAuthentication($request);
        if (!$attributeDefinition) {
            throw $this->createNotFoundException('Attribute definition not found.');
        }

        return $this->json(
            $this->normalizer->normalize($attributeDefinition),
            Response::HTTP_OK
        );
    }
}
