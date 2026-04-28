<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Controller;

use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Model\Bounce;
use PhpList\Core\Domain\Messaging\Repository\BounceRepository;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Service\Provider\PaginatedDataProvider;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Messaging\Serializer\BounceNormalizer;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use PhpList\Core\Domain\Messaging\Model\Filter\BounceFilter;

/**
 * Manage bounces.
 */
#[Route('/bounces', name: 'bounce_')]
class BounceController extends BaseController
{
    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        private readonly BounceRepository $bounceRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly BounceNormalizer $normalizer,
        private readonly PaginatedDataProvider $paginatedProvider,
    ) {
        parent::__construct($authentication, $validator);
    }

    #[Route('', name: 'get_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/bounces',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns a JSON list of all bounces.',
        summary: 'Gets a list of all bounces.',
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
            ),
            new OA\Parameter(
                name: 'status',
                description: 'Bounce status',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', default: 'unidentified bounce', maxLength: 100, minLength: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/BounceView')
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

        return $this->json(
            data: $this->paginatedProvider->getPaginatedList(
                request: $request,
                normalizer: $this->normalizer,
                className: Bounce::class,
                filter: (new BounceFilter())->setStatus($request->query->get('status'))
            ),
            status: Response::HTTP_OK
        );
    }

    #[Route('/{bounceId}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/v2/bounces/{bounceId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Delete a bounce by its id.',
        summary: 'Delete a bounce by its id',
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
                name: 'bounceId',
                description: 'Bounce id',
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
        #[MapEntity(mapping: ['bounceId' => 'id'])] ?Bounce $bounce = null
    ): JsonResponse {
        $this->requireAuthentication($request);
        if (!$bounce) {
            throw $this->createNotFoundException('Bounce regex not found.');
        }
        $this->bounceRepository->delete($bounce);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
