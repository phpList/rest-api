<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Model\Messaging\Template;
use PhpList\Core\Domain\Repository\Messaging\TemplateRepository;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Controller\Traits\AuthenticationTrait;
use PhpList\RestBundle\Serializer\TemplateNormalizer;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This controller provides REST API to manage templates.
 *
 * @author Tatevik Grigoryan <tatevik@phplist.com>
 */
#[Route('/templates')]
class TemplateController extends AbstractController
{
    use AuthenticationTrait;

    private TemplateRepository $templateRepository;
    private TemplateNormalizer $normalizer;

    public function __construct(
        Authentication $authentication,
        TemplateRepository $templateRepository,
        TemplateNormalizer $normalizer,
    ) {
        $this->authentication = $authentication;
        $this->templateRepository = $templateRepository;
        $this->normalizer = $normalizer;
    }

    #[Route('', name: 'get_templates', methods: ['GET'])]
    #[OA\Get(
        path: '/templates',
        description: 'Returns a JSON list of all templates.',
        summary: 'Gets a list of all templates.',
        tags: ['templates'],
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
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Template')
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            )
        ]
    )]
    public function getTemplates(Request $request): JsonResponse
    {
        $this->requireAuthentication($request);
        $data = $this->templateRepository->findAll();

        $normalized = array_map(function ($item) {
            return $this->normalizer->normalize($item);
        }, $data);

        return new JsonResponse($normalized, Response::HTTP_OK);
    }

    #[Route('/{templateId}', name: 'get_template', methods: ['GET'])]
    #[OA\Get(
        path: '/templates/{templateId}',
        description: 'Returns template by id.',
        summary: 'Gets a templateI by id.',
        tags: ['templates'],
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
                name: 'templateId',
                description: 'template ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Template')
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            )
        ]
    )]
    public function getTemplate(
        Request $request,
        #[MapEntity(mapping: ['templateId' => 'id'])] Template $template
    ): JsonResponse {
        $this->requireAuthentication($request);

        return new JsonResponse($this->normalizer->normalize($template), Response::HTTP_OK);
    }
}
