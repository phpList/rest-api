<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Model\Subscription\SubscriberAttributeDefinition;
use PhpList\Core\Domain\Service\Manager\AttributeDefinitionManager;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Entity\Request\CreateAttributeDefinitionRequest;
use PhpList\RestBundle\Entity\Request\UpdateAttributeDefinitionRequest;
use PhpList\RestBundle\Serializer\AttributeDefinitionNormalizer;
use PhpList\RestBundle\Service\Provider\PaginatedDataProvider;
use PhpList\RestBundle\Validator\RequestValidator;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/attributes')]
class AttributeDefinitionController extends BaseController
{
    private AttributeDefinitionManager $definitionManager;
    private AttributeDefinitionNormalizer $normalizer;
    private PaginatedDataProvider $paginatedDataProvider;

    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        AttributeDefinitionManager $definitionManager,
        AttributeDefinitionNormalizer $normalizer,
        PaginatedDataProvider $paginatedDataProvider
    ) {
        parent::__construct($authentication, $validator);
        $this->definitionManager = $definitionManager;
        $this->normalizer = $normalizer;
        $this->paginatedDataProvider = $paginatedDataProvider;
    }

    #[Route('', name: 'create_attribute_definition', methods: ['POST'])]
    #[OA\Post(
        path: '/attributes',
        description: 'Returns created subscriber attribute definition.',
        summary: 'Create a subscriber attribute definition.',
        requestBody: new OA\RequestBody(
            description: 'Pass parameters to create subscriber attribute.',
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', format: 'string', example: 'Country'),
                    new OA\Property(property: 'type', type: 'string', example: 'checkbox'),
                    new OA\Property(property: 'order', type: 'number', example: 12),
                    new OA\Property(property: 'default_value', type: 'string', example: 'United States'),
                    new OA\Property(property: 'required', type: 'boolean', example: true),
                    new OA\Property(property: 'table_name', type: 'string', example: 'list_attributes'),
                ]
            )
        ),
        tags: ['attributes'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID obtained from authentication',
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
                content: new OA\JsonContent(ref: '#/components/schemas/AttributeDefinition')
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
    public function create(Request $request,): JsonResponse
    {
        $this->requireAuthentication($request);

        /** @var CreateAttributeDefinitionRequest $definitionRequest */
        $definitionRequest = $this->validator->validate($request, CreateAttributeDefinitionRequest::class);

        $attributeDefinition = $this->definitionManager->create($definitionRequest->getDto());
        $json = $this->normalizer->normalize($attributeDefinition, 'json');

        return $this->json($json, Response::HTTP_CREATED);
    }

    #[Route('/{definitionId}', name: 'update_attribute_definition', methods: ['PUT'])]
    #[OA\Put(
        path: '/attributes/{definitionId}',
        description: 'Returns updated subscriber attribute definition.',
        summary: 'Update a subscriber attribute definition.',
        requestBody: new OA\RequestBody(
            description: 'Pass parameters to update subscriber attribute.',
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', format: 'string', example: 'Country'),
                    new OA\Property(property: 'type', type: 'string', example: 'checkbox'),
                    new OA\Property(property: 'order', type: 'number', example: 12),
                    new OA\Property(property: 'default_value', type: 'string', example: 'United States'),
                    new OA\Property(property: 'required', type: 'boolean', example: true),
                    new OA\Property(property: 'table_name', type: 'string', example: 'list_attributes'),
                ]
            )
        ),
        tags: ['attributes'],
        parameters: [
            new OA\Parameter(
                name: 'definitionId',
                description: 'Definition ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
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
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/AttributeDefinition')
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
        #[MapEntity(mapping: ['definitionId' => 'id'])] ?SubscriberAttributeDefinition $attributeDefinition,
    ): JsonResponse {
        $this->requireAuthentication($request);
        if (!$attributeDefinition) {
            throw $this->createNotFoundException('Attribute definition not found.');
        }

        /** @var UpdateAttributeDefinitionRequest $definitionRequest */
        $definitionRequest = $this->validator->validate($request, UpdateAttributeDefinitionRequest::class);

        $attributeDefinition = $this->definitionManager->update(
            $attributeDefinition,
            $definitionRequest->getDto(),
        );
        $json = $this->normalizer->normalize($attributeDefinition, 'json');

        return $this->json($json, Response::HTTP_OK);
    }

    #[Route('/{definitionId}', name: 'delete_attribute_definition', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/attributes/{definitionId}',
        description: 'Deletes a single subscriber attribute definition.',
        summary: 'Deletes an attribute definition.',
        tags: ['attributes'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID',
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
        #[MapEntity(mapping: ['definitionId' => 'id'])] ?SubscriberAttributeDefinition $attributeDefinition,
    ): JsonResponse {
        $this->requireAuthentication($request);
        if (!$attributeDefinition) {
            throw $this->createNotFoundException('Attribute definition not found.');
        }

        $this->definitionManager->delete($attributeDefinition);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('', name: 'get_attribute_definitions', methods: ['GET'])]
    #[OA\Get(
        path: '/attributes',
        description: 'Returns a JSON list of all subscriber attribute definitions.',
        summary: 'Gets a list of all subscriber attribute definitions.',
        tags: ['attributes'],
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
                            items: new OA\Items(ref: '#/components/schemas/AttributeDefinition')
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
    public function getPaginated(Request $request,): JsonResponse
    {
        $this->requireAuthentication($request);

        return $this->json(
            $this->paginatedDataProvider->getPaginatedList(
                $request,
                $this->normalizer,
                SubscriberAttributeDefinition::class,
            ),
            Response::HTTP_OK
        );
    }

    #[Route('/{definitionId}', name: 'get_attribute_definition', methods: ['GET'])]
    #[OA\Get(
        path: '/attributes/{definitionId}',
        description: 'Returns a single attribute with specified ID.',
        summary: 'Gets attribute with specified ID.',
        tags: ['attributes'],
        parameters: [
            new OA\Parameter(
                name: 'definitionId',
                description: 'Definition ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
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
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/AttributeDefinition')
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
        #[MapEntity(mapping: ['definitionId' => 'id'])] ?SubscriberAttributeDefinition $attributeDefinition,
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
