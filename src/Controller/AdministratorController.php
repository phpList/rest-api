<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Model\Identity\Administrator;
use PhpList\RestBundle\Controller\Traits\AuthenticationTrait;
use PhpList\RestBundle\Entity\Request\CreateAdministratorRequest;
use PhpList\RestBundle\Entity\Request\UpdateAdministratorRequest;
use PhpList\RestBundle\Serializer\AdministratorNormalizer;
use PhpList\RestBundle\Service\Manager\AdministratorManager;
use PhpList\RestBundle\Validator\RequestValidator;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This controller provides CRUD operations for Administrator entities.
 */
#[Route('/administrators')]
class AdministratorController extends AbstractController
{
    use AuthenticationTrait;

    private AdministratorManager $administratorManager;

    public function __construct(AdministratorManager $administratorManager)
    {
        $this->administratorManager = $administratorManager;
    }

    #[Route('', name: 'create_administrator', methods: ['POST'])]
    #[OA\Post(
        path: '/administrators',
        description: 'Create a new administrator.',
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
                content: new OA\JsonContent(ref: '#/components/schemas/CreateAdministratorRequest')
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
        /** @var CreateAdministratorRequest $dto */
        $dto = $validator->validate($request, CreateAdministratorRequest::class);

        $administrator = $this->administratorManager->createAdministrator($dto);

        $json = $normalizer->normalize($administrator, 'json');

        return new JsonResponse($json, Response::HTTP_CREATED);
    }

    #[Route('/{administratorId}', name: 'get_administrator', methods: ['GET'])]
    #[OA\Get(
        path: '/administrators/{administratorId}',
        description: 'Get administrator by ID.',
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
        #[MapEntity(mapping: ['administratorId' => 'id'])] ?Administrator $administrator,
        AdministratorNormalizer $normalizer
    ): JsonResponse {
        if (!$administrator) {
            return new JsonResponse(['message' => 'Administrator not found.'], Response::HTTP_NOT_FOUND);
        }

        $json = $normalizer->normalize($administrator, 'json');

        return new JsonResponse($json);
    }

    #[Route('/{administratorId}', name: 'update_administrator', methods: ['PUT'])]
    #[OA\Put(
        path: '/administrators/{administratorId}',
        description: 'Update an administrator.',
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
        RequestValidator $validator
    ): JsonResponse {
        if (!$administrator) {
            return new JsonResponse(['message' => 'Administrator not found.'], Response::HTTP_NOT_FOUND);
        }

        /** @var UpdateAdministratorRequest $dto */
        $dto = $validator->validate($request, UpdateAdministratorRequest::class);

        $this->administratorManager->updateAdministrator($administrator, $dto);

        return new JsonResponse(null, Response::HTTP_OK);
    }

    #[Route('/{administratorId}', name: 'delete_administrator', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/administrators/{administratorId}',
        description: 'Delete an administrator.',
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
        #[MapEntity(mapping: ['administratorId' => 'id'])] ?Administrator $administrator
    ): JsonResponse {
        if (!$administrator) {
            return new JsonResponse(['message' => 'Administrator not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->administratorManager->deleteAdministrator($administrator);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
