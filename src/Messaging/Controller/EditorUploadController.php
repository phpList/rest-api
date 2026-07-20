<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Common\Validator\UploadDirectoryValidator;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\Core\Domain\Common\Service\UploadService;
use PhpList\Core\Domain\Common\Service\DirectoryListingService;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Messaging\Serializer\EditorUploadNormalizer;
use PhpList\RestBundle\Messaging\Serializer\FileListingNormalizer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
        private readonly DirectoryListingService $directoryListingService,
        private readonly UploadDirectoryValidator $uploadDirectoryValidator,
        private readonly EditorUploadNormalizer $editorUploadNormalizer,
        private readonly FileListingNormalizer $fileListingNormalizer,
        #[Autowire('%phplist.upload_images_dir%')]
        private readonly string $uploadImagesDir,
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
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/EditorUpload')
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
            data: $this->editorUploadNormalizer->normalize($uploadResult),
            status: Response::HTTP_CREATED
        );
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/editor-uploads',
        description: 'Returns a list of files in the requested upload directory.',
        summary: 'List files in upload directory',
        tags: ['editor-uploads'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'directory',
                description: 'Upload directory name (e.g., "uploads" or "images")',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', default: 'uploads')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'files',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'name', type: 'string', example: 'image.png'),
                                    new OA\Property(
                                        property: 'url',
                                        type: 'string',
                                        example: 'https://ex.com/api/v2/editor-uploads/image.png'
                                    ),
                                    new OA\Property(property: 'size', type: 'integer', example: 12345),
                                    new OA\Property(property: 'type', type: 'string', example: 'file'),
                                    new OA\Property(property: 'modified', type: 'integer', example: 1689255600),
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(property: 'directory', type: 'string', example: 'uploads'),
                        new OA\Property(property: 'total', type: 'integer', example: 5),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request - invalid directory',
                content: new OA\JsonContent(ref: '#/components/schemas/BadRequestResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Directory not found',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundErrorResponse')
            ),
        ]
    )]
    public function listFiles(Request $request): JsonResponse
    {
        $this->requireAuthentication($request);

        $dir = trim($request->query->get('directory', ''), '/');
        $directory = $dir !== '' ? '/' . $dir : '';

        $realPath = $this->uploadDirectoryValidator->validate($this->uploadImagesDir . $directory);

        $files = $this->directoryListingService->list(directory: $directory, realPath: $realPath);

        return new JsonResponse(
            data: [
                'files' => array_map(
                    fn ($file) => $this->fileListingNormalizer->normalize($file),
                    $files
                ),
                'directory' => $directory,
                'total' => count($files),
            ],
            status: Response::HTTP_OK
        );
    }

    #[Route('/{filename}', name: 'get_file', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/editor-uploads/{filename}',
        description: 'Returns an uploaded image file by its name.',
        summary: 'Get uploaded image by name',
        tags: ['editor-uploads'],
        parameters: [
            new OA\Parameter(
                name: 'filename',
                description: 'Name of the uploaded image file',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\MediaType(mediaType: 'application/octet-stream')
            ),
            new OA\Response(
                response: 404,
                description: 'File not found',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundErrorResponse')
            ),
        ]
    )]
    public function getFile(string $filename): BinaryFileResponse
    {
        $realDirectory = $this->uploadDirectoryValidator->validate($this->uploadImagesDir);

        $requestedPath = $realDirectory . DIRECTORY_SEPARATOR . basename($filename);
        $realPath = realpath($requestedPath);

        if ($realPath === false
            || !str_starts_with($realPath, $realDirectory . DIRECTORY_SEPARATOR)
            || !is_file($realPath)
        ) {
            throw $this->createNotFoundException(sprintf('File "%s" not found.', $filename));
        }

        return $this->file($realPath);
    }
}
