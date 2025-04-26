<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Model\Messaging\Template;
use PhpList\Core\Domain\Repository\Messaging\TemplateRepository;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Controller\Traits\AuthenticationTrait;
use PhpList\RestBundle\Entity\Request\CreateTemplateRequest;
use PhpList\RestBundle\Serializer\TemplateNormalizer;
use PhpList\RestBundle\Service\Manager\TemplateManager;
use PhpList\RestBundle\Validator\RequestValidator;
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
    private RequestValidator $validator;
    private TemplateManager $templateManager;

    public function __construct(
        Authentication $authentication,
        TemplateRepository $templateRepository,
        TemplateNormalizer $normalizer,
        RequestValidator $validator,
        TemplateManager $templateManager
    ) {
        $this->authentication = $authentication;
        $this->templateRepository = $templateRepository;
        $this->normalizer = $normalizer;
        $this->validator = $validator;
        $this->templateManager = $templateManager;
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

    #[Route('', name: 'create_template', methods: ['POST'])]
    #[OA\Post(
        path: '/templates',
        description: 'Returns a JSON response of created template.',
        summary: 'Create a new template.',
        requestBody: new OA\RequestBody(
            description: 'Pass session credentials',
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['title'],
                    properties: [
                        new OA\Property(
                            property: 'title',
                            type: 'string',
                            example: 'Newsletter Template'
                        ),
                        new OA\Property(
                            property: 'content',
                            type: 'string',
                            example: '<html><body>[CONTENT]</body></html>'
                        ),
                        new OA\Property(
                            property: 'text',
                            type: 'string',
                            example: '[CONTENT]'
                        ),
                        new OA\Property(
                            property: 'file',
                            description: 'Optional file upload for HTML content',
                            type: 'string',
                            format: 'binary'
                        ),
                        new OA\Property(
                            property: 'check_links',
                            description: 'Check that all links have full URLs',
                            type: 'boolean',
                            example: true
                        ),
                        new OA\Property(
                            property: 'check_images',
                            description: 'Check that all images have full URLs',
                            type: 'boolean',
                            example: false
                        ),
                        new OA\Property(
                            property: 'check_external_images',
                            description: 'Check that all external images exist',
                            type: 'boolean',
                            example: true
                        ),
                    ],
                    type: 'object'
                )
            )
        ),
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
                response: 201,
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
            ),
            new OA\Response(
                response: 422,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function createTemplates(Request $request): JsonResponse
    {
        $this->requireAuthentication($request);

        /** @var CreateTemplateRequest $createTemplateRequest */
        $createTemplateRequest = $this->validator->validate($request, CreateTemplateRequest::class);

        return new JsonResponse(
            $this->normalizer->normalize($this->templateManager->create($createTemplateRequest)),
            Response::HTTP_CREATED
        );
    }

    #[Route('/{templateId}', name: 'delete_template', methods: ['DELETE'])]
    #[OA\Delete(
        path: 'templates/{templateId}',
        description: 'Deletes template by id.',
        summary: 'Deletes a template.',
        tags: ['templates'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'templateId',
                description: 'Template ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
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
        #[MapEntity(mapping: ['templateId' => 'id'])] Template $template
    ): JsonResponse {
        $this->requireAuthentication($request);

        $this->templateManager->delete($template);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
