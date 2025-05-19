<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Subscription\Model\Filter\SubscriberAttributeValueFilter;
use PhpList\Core\Domain\Subscription\Model\Subscriber;
use PhpList\Core\Domain\Subscription\Model\SubscriberAttributeDefinition;
use PhpList\Core\Domain\Subscription\Model\SubscriberAttributeValue;
use PhpList\Core\Domain\Subscription\Service\SubscriberAttributeManager;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Service\Provider\PaginatedDataProvider;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Subscription\Serializer\SubscriberAttributeValueNormalizer;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/subscribers/attribute-values', name: 'subscriber_attribute_value_')]
class SubscriberAttributeValueController extends BaseController
{
    private SubscriberAttributeManager $attributeManager;
    private SubscriberAttributeValueNormalizer $normalizer;
    private PaginatedDataProvider $paginatedDataProvider;

    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        SubscriberAttributeManager $attributeManager,
        SubscriberAttributeValueNormalizer $normalizer,
        PaginatedDataProvider $paginatedDataProvider
    ) {
        parent::__construct($authentication, $validator);
        $this->attributeManager = $attributeManager;
        $this->normalizer = $normalizer;
        $this->paginatedDataProvider = $paginatedDataProvider;
    }

    #[Route(
        path: '/{subscriberId}/{definitionId}',
        name: 'create',
        requirements: ['subscriberId' => '\d+', 'definitionId' => '\d+'],
        methods: ['POST', 'PUT']
    )]
    #[OA\Post(
        path: '/subscriber/attribute-values/{subscriberId}/{definitionId}',
        description: 'Returns created/updated subscriber attribute.',
        summary: 'Create/update a subscriber attribute.',
        requestBody: new OA\RequestBody(
            description: 'Pass parameters to create subscriber attribute.',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'value', type: 'string', example: 'United States'),
                ]
            )
        ),
        tags: ['subscriber-attributes'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID obtained from authentication',
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
                name: 'subscriberId',
                description: 'Subscriber id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/SubscriberAttributeValue')
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
        #[MapEntity(mapping: ['definitionId' => 'id'])] ?SubscriberAttributeDefinition $definition = null,
        #[MapEntity(mapping: ['subscriberId' => 'id'])] ?Subscriber $subscriber = null,
    ): JsonResponse {
        $this->requireAuthentication($request);

        if (!$definition) {
            throw $this->createNotFoundException('Attribute definition not found.');
        }
        if (!$subscriber) {
            throw $this->createNotFoundException('Subscriber not found.');
        }

        $attributeDefinition = $this->attributeManager->createOrUpdate(
            subscriber:$subscriber,
            definition: $definition,
            value: $request->toArray()['value'] ?? null
        );
        $json = $this->normalizer->normalize($attributeDefinition, 'json');

        return $this->json($json, Response::HTTP_CREATED);
    }

    #[Route(
        path: '/{subscriberId}/{definitionId}',
        name: 'delete',
        requirements: ['subscriberId' => '\d+', 'definitionId' => '\d+'],
        methods: ['DELETE']
    )]
    #[OA\Delete(
        path: '/subscriber/attribute-values/{subscriberId}/{definitionId}',
        description: 'Deletes a single subscriber attribute.',
        summary: 'Deletes an attribute.',
        tags: ['subscriber-attributes'],
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
                description: 'attribute definition id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'subscriberId',
                description: 'Subscriber id',
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
        #[MapEntity(mapping: ['definitionId' => 'id'])] ?SubscriberAttributeDefinition $definition = null,
        #[MapEntity(mapping: ['subscriberId' => 'id'])] ?Subscriber $subscriber = null,
    ): JsonResponse {
        $this->requireAuthentication($request);
        if (!$definition || !$subscriber) {
            throw $this->createNotFoundException('Subscriber attribute not found.');
        }
        $attribute = $this->attributeManager->getSubscriberAttribute($subscriber->getId(), $definition->getId());
        if ($attribute === null) {
            throw $this->createNotFoundException('Subscriber attribute not found.');
        }
        $this->attributeManager->delete($attribute);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{subscriberId}', name: 'get_list', requirements: ['subscriberId' => '\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/subscribers/attribute-values/{subscriberId}',
        description: 'Returns a JSON list of all subscriber attributes.',
        summary: 'Gets a list of all subscriber attributes.',
        tags: ['subscriber-attributes'],
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
                description: 'Subscriber id',
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
                            items: new OA\Items(ref: '#/components/schemas/SubscriberAttributeValue')
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
        #[MapEntity(mapping: ['subscriberId' => 'id'])] ?Subscriber $subscriber = null,
    ): JsonResponse {
        $this->requireAuthentication($request);

        $filter = (new SubscriberAttributeValueFilter())->setSubscriberId($subscriber->getId());

        return $this->json(
            $this->paginatedDataProvider->getPaginatedList(
                $request,
                $this->normalizer,
                SubscriberAttributeValue::class,
                $filter
            ),
            Response::HTTP_OK
        );
    }

    #[Route(
        path: '/{subscriberId}/{definitionId}',
        name: 'get_one',
        requirements: ['subscriberId' => '\d+', 'definitionId' => '\d+'],
        methods: ['GET']
    )]
    #[OA\Get(
        path: '/subscribers/attribute-values/{subscriberId}/{definitionId}',
        description: 'Returns a single attribute.',
        summary: 'Gets subscriber attribute.',
        tags: ['subscriber-attributes'],
        parameters: [
            new OA\Parameter(
                name: 'definitionId',
                description: 'attribute definition id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'subscriberId',
                description: 'Subscriber id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
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
                content: new OA\JsonContent(ref: '#/components/schemas/SubscriberAttributeValue')
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
        #[MapEntity(mapping: ['subscriberId' => 'id'])] ?SubscriberAttributeDefinition $subscriber,
        #[MapEntity(mapping: ['definitionId' => 'id'])] ?SubscriberAttributeDefinition $definition,
    ): JsonResponse {
        $this->requireAuthentication($request);
        if (!$definition || !$subscriber) {
            throw $this->createNotFoundException('Subscriber attribute not found.');
        }
        $attribute = $this->attributeManager->getSubscriberAttribute($subscriber->getId(), $definition->getId());
        $this->attributeManager->delete($attribute);

        return $this->json(
            $this->normalizer->normalize($attribute),
            Response::HTTP_OK
        );
    }
}
