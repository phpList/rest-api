<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Model\Template;
use PhpList\Core\Domain\Messaging\Service\Manager\TemplateManager;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Service\Provider\PaginatedDataProvider;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Messaging\Request\CreateTemplateRequest;
use PhpList\RestBundle\Messaging\Serializer\TemplateNormalizer;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This controller provides REST API to manage templates.
 *
 * @author Tatevik Grigoryan <tatevik@phplist.com>
 */
#[Route('/templates', name: 'template_')]
class TemplateController extends BaseController
{
    private TemplateNormalizer $normalizer;
    private TemplateManager $templateManager;
    private PaginatedDataProvider $paginatedDataProvider;

    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        TemplateNormalizer $normalizer,
        TemplateManager $templateManager,
        PaginatedDataProvider $paginatedDataProvider,
    ) {
        parent::__construct($authentication, $validator);
        $this->normalizer = $normalizer;
        $this->templateManager = $templateManager;
        $this->paginatedDataProvider = $paginatedDataProvider;
    }

    #[Route('', name: 'get_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/templates',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns a JSON list of all templates.',
        summary: 'Gets a list of all templates.',
        tags: ['templates'],
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
                            items: new OA\Items(ref: '#/components/schemas/Template')
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
    public function getTemplates(Request $request): JsonResponse
    {
        $this->requireAuthentication($request);

        return $this->json(
            $this->paginatedDataProvider->getPaginatedList(
                $request,
                $this->normalizer,
                Template::class,
            ),
            Response::HTTP_OK
        );
    }

    #[Route('/{templateId}', name: 'get_one', requirements: ['templateId' => '\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/templates/{templateId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns template by id.',
        summary: 'Gets a templateI by id.',
        tags: ['templates'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
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
            ),
            new OA\Response(
                response: 404,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundErrorResponse')
            )
        ]
    )]
    public function getTemplate(
        Request $request,
        #[MapEntity(mapping: ['templateId' => 'id'])] ?Template $template = null,
    ): JsonResponse {
        $this->requireAuthentication($request);

        if (!$template) {
            throw $this->createNotFoundException('Template not found.');
        }

        return $this->json($this->normalizer->normalize($template), Response::HTTP_OK);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v2/templates',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns a JSON response of created template.',
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

        return $this->json(
            $this->normalizer->normalize($this->templateManager->create($createTemplateRequest->getDto())),
            Response::HTTP_CREATED
        );
    }

    #[Route('/{templateId}', name: 'delete', requirements: ['templateId' => '\d+'], methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/v2/templates/{templateId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Deletes template by id.',
        summary: 'Deletes a template.',
        tags: ['templates'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
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
        #[MapEntity(mapping: ['templateId' => 'id'])] ?Template $template = null,
    ): JsonResponse {
        $this->requireAuthentication($request);

        if (!$template) {
            throw $this->createNotFoundException('Template not found.');
        }

        $this->templateManager->delete($template);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
