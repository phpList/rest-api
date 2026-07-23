<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Configuration\Controller;

use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Configuration\Exception\ConfigNotEditableException;
use PhpList\Core\Domain\Configuration\Model\Config;
use PhpList\Core\Domain\Configuration\Service\Manager\ConfigManager;
use PhpList\Core\Domain\Identity\Model\PrivilegeFlag;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Configuration\Request\CreateConfigRequest;
use PhpList\RestBundle\Configuration\Request\UpdateConfigRequest;
use PhpList\RestBundle\Configuration\Serializer\ConfigNormalizer;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/configs', name: 'config_')]
class ConfigController extends BaseController
{
    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        private readonly ConfigManager $manager,
        private readonly ConfigNormalizer $normalizer,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct($authentication, $validator);
    }

    #[Route('', name: 'get_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/configs',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns all configuration items.',
        summary: 'Gets all configuration items.',
        tags: ['configs'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
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
                            items: new OA\Items(ref: '#/components/schemas/Config')
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
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $this->denyUnlessSettingsAdmin($request, 'You are not allowed to view configuration.');
        $items = $this->manager->getAllEditable();

        usort(
            $items,
            fn (Config $aConf, Config $bConf): int => strcmp(
                strtolower($aConf->getKey()),
                strtolower($bConf->getKey())
            )
        );

        $count = count($items);

        return $this->json(
            data: [
                'items' => array_map(fn($config) => $this->normalizer->normalize($config), $items),
                'pagination' => [
                    'total' => $count,
                    'limit' => $count + 1,
                    'has_more' => false,
                    'next_cursor' => null
                ]
            ],
            status: Response::HTTP_OK
        );
    }

    #[Route('/{key}', name: 'get_one', requirements: ['key' => '[A-Za-z0-9_.:-]+'], methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/configs/{key}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns one configuration item by key.',
        summary: 'Gets a configuration item.',
        tags: ['configs'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'key',
                description: 'Configuration key',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Config')
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
            ),
        ]
    )]
    public function getOne(
        Request $request,
        #[MapEntity(mapping: ['key' => 'key'])] ?Config $config,
    ): JsonResponse {
        $this->denyUnlessSettingsAdmin($request, 'You are not allowed to view configuration.');
        if ($config === null) {
            throw $this->createNotFoundException('Configuration item not found.');
        }

        return $this->json($this->normalizer->normalize($config), Response::HTTP_OK);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v2/configs',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Creates a configuration item.',
        summary: 'Creates a configuration item.',
        requestBody: new OA\RequestBody(
            description: 'Configuration item data',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ConfigRequest')
        ),
        tags: ['configs'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Config')
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
    public function create(Request $request): JsonResponse
    {
        $this->denyUnlessSettingsAdmin($request, 'You are not allowed to create configuration.');
        /* @var CreateConfigRequest $configRequest */
        $configRequest = $this->validator->validate($request, CreateConfigRequest::class);

        $config = $this->manager->create($configRequest->getDto());
        $this->entityManager->flush();

        return $this->json($this->normalizer->normalize($config), Response::HTTP_CREATED);
    }

    #[Route('/{key}', name: 'update', requirements: ['key' => '[A-Za-z0-9_.:-]+'], methods: ['PUT'])]
    #[OA\Put(
        path: '/api/v2/configs/{key}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Updates a configuration item value.',
        summary: 'Updates a configuration item.',
        requestBody: new OA\RequestBody(
            description: 'Configuration item data',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ConfigUpdateRequest')
        ),
        tags: ['configs'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'key',
                description: 'Configuration key',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Config')
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
        #[MapEntity(mapping: ['key' => 'key'])] ?Config $config = null
    ): JsonResponse {
        $this->denyUnlessSettingsAdmin($request, 'You are not allowed to update configuration.');
        if ($config === null) {
            throw $this->createNotFoundException('Configuration item not found.');
        }
        /* @var UpdateConfigRequest $dto */
        $dto = $this->validator->validate($request, UpdateConfigRequest::class);

        try {
            $this->manager->update($config, $dto->getDto()['value']);
        } catch (ConfigNotEditableException $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        }
        $this->entityManager->flush();

        return $this->json($this->normalizer->normalize($config), Response::HTTP_OK);
    }

    #[Route('/{key}', name: 'delete', requirements: ['key' => '[A-Za-z0-9_.:-]+'], methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/v2/configs/{key}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Deletes a configuration item.',
        summary: 'Deletes a configuration item.',
        tags: ['configs'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'key',
                description: 'Configuration key',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Success'),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundErrorResponse')
            ),
        ]
    )]
    public function delete(
        Request $request,
        #[MapEntity(mapping: ['key' => 'key'])] ?Config $config = null
    ): JsonResponse {
        $this->denyUnlessSettingsAdmin($request, 'You are not allowed to delete configuration.');
        if ($config === null) {
            throw $this->createNotFoundException('Configuration item not found.');
        }

        $this->manager->delete($config);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function denyUnlessSettingsAdmin(Request $request, string $message): void
    {
        $admin = $this->requireAuthentication($request);
        if (!$admin->getPrivileges()->has(PrivilegeFlag::Settings)) {
            throw $this->createAccessDeniedException($message);
        }
    }
}
