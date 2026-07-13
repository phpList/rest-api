<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\Core\Domain\Common\Upload\UploadService;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/editor-uploads', name: 'editor_uploads_')]
class EditorUploadController extends BaseController
{
    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        private readonly UploadService $uploadService,
    ) {
        parent::__construct($authentication, $validator);
    }

    #[Route('', name: 'upload', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v2/editor-uploads',
        description: 'Uploads an editor asset for use in CKEditor.',
        summary: 'Upload editor asset',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(
                            property: 'upload',
                            description: 'Asset file to upload',
                            type: 'string',
                            format: 'binary'
                        ),
                        new OA\Property(
                            property: 'file',
                            description: 'Alternative asset file field name',
                            type: 'string',
                            format: 'binary'
                        ),
                    ],
                    type: 'object'
                )
            )
        ),
        tags: ['editor-uploads'],
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
                description: 'Asset uploaded',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'uploaded', type: 'boolean', example: true),
                        new OA\Property(property: 'fileName', type: 'string', example: 'test.png'),
                        new OA\Property(property: 'url', type: 'string', example: 'https://ex.com/uploads/test.png'),
                        new OA\Property(
                            property: 'default',
                            type: 'string',
                            example: 'https://ex.com/uploads/test.png'
                        ),
                        new OA\Property(property: 'mimeType', type: 'string', example: 'image/png'),
                        new OA\Property(property: 'size', type: 'integer', example: 123456, nullable: true),
                        new OA\Property(property: 'extension', type: 'string', example: 'png'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request',
                content: new OA\JsonContent(ref: '#/components/schemas/BadRequestResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
            new OA\Response(
                response: 413,
                description: 'Payload too large',
                content: new OA\JsonContent(ref: '#/components/schemas/GenericErrorResponse')
            ),
            new OA\Response(
                response: 500,
                description: 'Storage failure',
                content: new OA\JsonContent(ref: '#/components/schemas/GenericErrorResponse')
            ),
        ]
    )]
    public function uploadAsset(Request $request): JsonResponse
    {
        $this->requireAuthentication($request);

        /** @var UploadedFile|null $uploadedFile */
        $uploadedFile = $request->files->get('upload') ?? $request->files->get('file');
        $uploadResult = $this->uploadService->upload($uploadedFile);

        return new JsonResponse(
            [
                'uploaded' => true,
                'fileName' => $uploadResult->getFilename(),
                'url' => $uploadResult->getUrl(),
                'default' => $uploadResult->getUrl(),
                'mimeType' => $uploadResult->getMimeType(),
                'size' => $uploadResult->getSize(),
                'extension' => $uploadResult->getExtension(),
            ],
            Response::HTTP_CREATED
        );
    }
}
