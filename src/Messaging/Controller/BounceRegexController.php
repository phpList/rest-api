<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Controller;

use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Model\BounceRegex;
use PhpList\Core\Domain\Messaging\Service\Manager\BounceRegexManager;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Messaging\Request\BounceRegexRequest;
use PhpList\RestBundle\Messaging\Serializer\BounceRegexNormalizer;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
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
        private readonly EntityManagerInterface $entityManager,
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

    #[Route('/{ruleId}', name: 'get_one', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/bounces/regex/{ruleId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns a bounce regex by its ID.',
        summary: 'Get a bounce regex by its ID',
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
                name: 'ruleId',
                description: 'Regex ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
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
    public function getOne(
        Request $request,
        #[MapEntity(mapping: ['ruleId' => 'id'])] ?BounceRegex $bounceRegex = null,
    ): JsonResponse {
        $this->requireAuthentication($request);
        if (!$bounceRegex) {
            throw $this->createNotFoundException('Bounce regex not found.');
        }

        return $this->json($this->normalizer->normalize($bounceRegex), Response::HTTP_OK);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v2/bounces/regex',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Creates a new bounce regex.',
        summary: 'Create a bounce regex',
        requestBody: new OA\RequestBody(
            description: 'Create bounce regex rule',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BounceRegexRequest')
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
    public function create(Request $request): JsonResponse
    {
        $authAdmin = $this->requireAuthentication($request);
        /** @var BounceRegexRequest $dto */
        $dto = $this->validator->validate($request, BounceRegexRequest::class);

        $entity = $this->manager->create(
            regex: $dto->regex,
            admin: $authAdmin,
            action: $dto->action,
            listOrder: $dto->listOrder,
            comment: $dto->comment,
            status: $dto->status
        );
        $this->entityManager->flush();

        return $this->json(data: $this->normalizer->normalize($entity), status: Response::HTTP_CREATED);
    }

    #[Route('/{ruleId}', name: 'update', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/v2/bounces/regex/{ruleId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
        'Updates an existing one.',
        summary: 'Update a bounce regex',
        requestBody: new OA\RequestBody(
            description: 'Update a bounce regex rule.',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BounceRegexRequest')
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
            new OA\Parameter(
                name: 'ruleId',
                description: 'regex rule ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
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
        #[MapEntity(mapping: ['ruleId' => 'id'])] ?BounceRegex $bounceRegex = null,
    ): JsonResponse {
        $this->requireAuthentication($request);
        if (!$bounceRegex) {
            throw $this->createNotFoundException('Bounce regex not found.');
        }
        /** @var BounceRegexRequest $dto */
        $dto = $this->validator->validate($request, BounceRegexRequest::class);

        $entity = $this->manager->update(
            bounceRegex: $bounceRegex,
            regex: $dto->regex,
            action: $dto->action,
            listOrder: $dto->listOrder,
            comment: $dto->comment,
            status: $dto->status
        );
        $this->entityManager->flush();

        return $this->json($this->normalizer->normalize($entity), Response::HTTP_CREATED);
    }

    #[Route('/{ruleId}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/v2/bounces/regex/{ruleId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Delete a bounce regex by its id.',
        summary: 'Delete a bounce regex by its id',
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
                name: 'ruleId',
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
    public function delete(
        Request $request,
        #[MapEntity(mapping: ['ruleId' => 'id'])] ?BounceRegex $bounceRegex = null,
    ): JsonResponse {
        $this->requireAuthentication($request);
        if (!$bounceRegex) {
            throw $this->createNotFoundException('Bounce regex not found.');
        }
        $this->manager->delete($bounceRegex);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
