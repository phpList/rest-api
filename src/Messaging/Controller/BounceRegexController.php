<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Service\Manager\BounceRegexManager;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Messaging\Request\CreateBounceRegexRequest;
use PhpList\RestBundle\Messaging\Serializer\BounceRegexNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Manage bounce regular expressions.
 */
#[Route('/bounces/regex', name: 'bounce_regex_')]
class BounceRegexController extends BaseController
{
    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        private readonly BounceRegexManager $manager,
        private readonly BounceRegexNormalizer $normalizer,
    ) {
        parent::__construct($authentication, $validator);
    }

    #[Route('', name: 'get_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/bounces/regex',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns a JSON list of all bounce regex rules.',
        summary: 'Gets a list of all bounce regex rules.',
        tags: ['bounces'],
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
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/BounceRegex')
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            )
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $this->requireAuthentication($request);
        $items = $this->manager->getAll();
        $normalized = array_map(fn($bounceRegex) => $this->normalizer->normalize($bounceRegex), $items);

        return $this->json($normalized, Response::HTTP_OK);
    }

    #[Route('/{regexHash}', name: 'get_one', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/bounces/regex/{regexHash}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns a bounce regex by its hash.',
        summary: 'Get a bounce regex by its hash',
        tags: ['bounces'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'regexHash',
                description: 'Regex hash',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/BounceRegex')
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
    public function getOne(Request $request, string $regexHash): JsonResponse
    {
        $this->requireAuthentication($request);
        $entity = $this->manager->getByHash($regexHash);
        if (!$entity) {
            throw $this->createNotFoundException('Bounce regex not found.');
        }

        return $this->json($this->normalizer->normalize($entity), Response::HTTP_OK);
    }

    #[Route('', name: 'create_or_update', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v2/bounces/regex',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Creates a new bounce regex or updates an existing one (matched by regex hash).',
        summary: 'Create or update a bounce regex',
        requestBody: new OA\RequestBody(
            description: 'Create or update a bounce regex rule.',
            required: true,
            content: new OA\JsonContent(
                required: ['regex'],
                properties: [
                    new OA\Property(property: 'regex', type: 'string', example: '/mailbox is full/i'),
                    new OA\Property(property: 'action', type: 'string', example: 'delete', nullable: true),
                    new OA\Property(property: 'list_order', type: 'integer', example: 0, nullable: true),
                    new OA\Property(property: 'admin', type: 'integer', example: 1, nullable: true),
                    new OA\Property(property: 'comment', type: 'string', example: 'Auto-generated', nullable: true),
                    new OA\Property(property: 'status', type: 'string', example: 'active', nullable: true),
                ],
                type: 'object'
            )
        ),
        tags: ['bounces'],
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
                content: new OA\JsonContent(ref: '#/components/schemas/BounceRegex')
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
    public function createOrUpdate(Request $request): JsonResponse
    {
        $this->requireAuthentication($request);
        /** @var CreateBounceRegexRequest $dto */
        $dto = $this->validator->validate($request, CreateBounceRegexRequest::class);

        $entity = $this->manager->createOrUpdateFromPattern(
            regex: $dto->regex,
            action: $dto->action,
            listOrder: $dto->listOrder,
            adminId: $dto->admin,
            comment: $dto->comment,
            status: $dto->status
        );

        return $this->json($this->normalizer->normalize($entity), Response::HTTP_CREATED);
    }

    #[Route('/{regexHash}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/v2/bounces/regex/{regexHash}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Delete a bounce regex by its hash.',
        summary: 'Delete a bounce regex by its hash',
        tags: ['bounces'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'regexHash',
                description: 'Regex hash',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
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
    public function delete(Request $request, string $regexHash): JsonResponse
    {
        $this->requireAuthentication($request);
        $entity = $this->manager->getByHash($regexHash);
        if (!$entity) {
            throw $this->createNotFoundException('Bounce regex not found.');
        }
        $this->manager->delete($entity);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
