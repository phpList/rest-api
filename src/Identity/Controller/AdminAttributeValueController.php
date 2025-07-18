<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Identity\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Identity\Model\Filter\AdminAttributeValueFilter;
use PhpList\Core\Domain\Identity\Model\Administrator;
use PhpList\Core\Domain\Identity\Model\AdminAttributeDefinition;
use PhpList\Core\Domain\Identity\Model\AdminAttributeValue;
use PhpList\Core\Domain\Identity\Service\AdminAttributeManager;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Service\Provider\PaginatedDataProvider;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Identity\Serializer\AdminAttributeValueNormalizer;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/administrators/attribute-values', name: 'admin_attribute_value_')]
class AdminAttributeValueController extends BaseController
{
    private AdminAttributeManager $attributeManager;
    private AdminAttributeValueNormalizer $normalizer;
    private PaginatedDataProvider $paginatedDataProvider;

    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        AdminAttributeManager $attributeManager,
        AdminAttributeValueNormalizer $normalizer,
        PaginatedDataProvider $paginatedDataProvider
    ) {
        parent::__construct($authentication, $validator);
        $this->attributeManager = $attributeManager;
        $this->normalizer = $normalizer;
        $this->paginatedDataProvider = $paginatedDataProvider;
    }

    #[Route(
        path: '/{adminId}/{definitionId}',
        name: 'create',
        requirements: ['adminId' => '\d+', 'definitionId' => '\d+'],
        methods: ['POST', 'PUT'],
    )]
    #[OA\Post(
        path: '/api/v2/administrators/attribute-values/{adminId}/{definitionId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns created/updated admin attribute.',
        summary: 'Create/update an admin attribute.',
        requestBody: new OA\RequestBody(
            description: 'Pass parameters to create admin attribute.',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'value', type: 'string', example: 'United States'),
                ]
            )
        ),
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
                description: 'attribute definition id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'adminId',
                description: 'Administrator id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/AdminAttributeValue')
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
    public function createOrUpdate(
        Request $request,
        #[MapEntity(mapping: ['definitionId' => 'id'])] ?AdminAttributeDefinition $definition = null,
        #[MapEntity(mapping: ['adminId' => 'id'])] ?Administrator $admin = null,
    ): JsonResponse {
        $this->requireAuthentication($request);

        if (!$definition) {
            throw $this->createNotFoundException('Attribute definition not found.');
        }
        if (!$admin) {
            throw $this->createNotFoundException('Administrator not found.');
        }

        $attributeDefinition = $this->attributeManager->createOrUpdate(
            admin:$admin,
            definition: $definition,
            value: $request->toArray()['value'] ?? null
        );
        $json = $this->normalizer->normalize($attributeDefinition, 'json');

        return $this->json($json, Response::HTTP_CREATED);
    }

    #[Route(
        path: '/{adminId}/{definitionId}',
        name: 'delete',
        requirements: ['adminId' => '\d+', 'definitionId' => '\d+'],
        methods: ['DELETE'],
    )]
    #[OA\Delete(
        path: '/api/v2/administrators/attribute-values/{adminId}/{definitionId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Deletes a single admin attribute.',
        summary: 'Deletes an attribute.',
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
                description: 'attribute definition id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'adminId',
                description: 'Administrator id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
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
        #[MapEntity(mapping: ['definitionId' => 'id'])] ?AdminAttributeDefinition $definition = null,
        #[MapEntity(mapping: ['adminId' => 'id'])] ?Administrator $admin = null,
    ): JsonResponse {
        $this->requireAuthentication($request);
        if (!$definition || !$admin) {
            throw $this->createNotFoundException('Administrator attribute not found.');
        }
        $attribute = $this->attributeManager->getAdminAttribute($admin->getId(), $definition->getId());
        if ($attribute === null) {
            throw $this->createNotFoundException('Administrator attribute not found.');
        }
        $this->attributeManager->delete($attribute);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{adminId}', name: 'get__list', requirements: ['adminId' => '\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/administrators/attribute-values/{adminId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns a JSON list of all admin attributes.',
        summary: 'Gets a list of all admin attributes.',
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
                name: 'adminId',
                description: 'Administrator id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
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
                            items: new OA\Items(ref: '#/components/schemas/AdminAttributeValue')
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
    public function getPaginated(
        Request $request,
        #[MapEntity(mapping: ['adminId' => 'id'])] ?Administrator $admin = null,
    ): JsonResponse {
        $this->requireAuthentication($request);
        if (!$admin) {
            throw $this->createNotFoundException('Administrator not found.');
        }

        $filter = (new AdminAttributeValueFilter())->setAdminId($admin->getId());

        return $this->json(
            $this->paginatedDataProvider->getPaginatedList(
                $request,
                $this->normalizer,
                AdminAttributeValue::class,
                $filter
            ),
            Response::HTTP_OK
        );
    }

    #[Route('/{adminId}/{definitionId}', name: 'get_one', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/administrators/attribute-values/{adminId}/{definitionId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns a single attribute.',
        summary: 'Gets admin attribute.',
        tags: ['admin-attributes'],
        parameters: [
            new OA\Parameter(
                name: 'definitionId',
                description: 'attribute definition id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'adminId',
                description: 'Administrator id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
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
                content: new OA\JsonContent(ref: '#/components/schemas/AdminAttributeValue')
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
        #[MapEntity(mapping: ['adminId' => 'id'])] ?AdminAttributeDefinition $admin,
        #[MapEntity(mapping: ['definitionId' => 'id'])] ?AdminAttributeDefinition $definition,
    ): JsonResponse {
        $this->requireAuthentication($request);
        if (!$definition || !$admin) {
            throw $this->createNotFoundException('Administrator attribute not found.');
        }
        $attribute = $this->attributeManager->getAdminAttribute(
            adminId: $admin->getId(),
            attributeDefinitionId: $definition->getId()
        );
        $this->attributeManager->delete($attribute);

        return $this->json(
            $this->normalizer->normalize($attribute),
            Response::HTTP_OK
        );
    }
}
