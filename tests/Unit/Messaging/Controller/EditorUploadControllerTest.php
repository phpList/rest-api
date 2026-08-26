<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Controller;

use PhpList\Core\Domain\Common\Model\Dto\DirectoryEntryDto;
use PhpList\Core\Domain\Common\Service\DirectoryListingService;
use PhpList\Core\Domain\Common\Validator\UploadDirectoryValidator;
use PhpList\Core\Domain\Identity\Model\Administrator;
use PhpList\Core\Domain\Identity\Service\Authentication;
use PhpList\Core\Domain\Common\Model\UploadResult;
use PhpList\Core\Domain\Common\Service\UploadService;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Messaging\Controller\EditorUploadController;
use PhpList\RestBundle\Messaging\Serializer\EditorUploadNormalizer;
use PhpList\RestBundle\Messaging\Serializer\FileListingNormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EditorUploadControllerTest extends TestCase
{
    private EditorUploadController $controller;
    private Authentication|MockObject $authentication;
    private UploadService|MockObject $uploadService;
    private DirectoryListingService|MockObject $directoryListingService;
    private UploadDirectoryValidator|MockObject $uploadDirectoryValidator;
    private UrlGeneratorInterface|MockObject $urlGenerator;
    private string $uploadDir;

    protected function setUp(): void
    {
        $this->authentication = $this->createMock(Authentication::class);
        $validator = $this->createMock(RequestValidator::class);
        $this->uploadService = $this->createMock(UploadService::class);
        $this->directoryListingService = $this->createMock(DirectoryListingService::class);
        $this->uploadDirectoryValidator = $this->createMock(UploadDirectoryValidator::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $this->uploadDir = sys_get_temp_dir() . '/editor_uploads_test_' . bin2hex(random_bytes(4));
        mkdir($this->uploadDir);
        file_put_contents($this->uploadDir . '/test.png', 'fake-image-content');

        $this->controller = new EditorUploadController(
            authentication: $this->authentication,
            validator: $validator,
            uploadService: $this->uploadService,
            directoryListingService: $this->directoryListingService,
            uploadDirectoryValidator: $this->uploadDirectoryValidator,
            editorUploadNormalizer: new EditorUploadNormalizer(),
            fileListingNormalizer: new FileListingNormalizer($this->urlGenerator),
            uploadImagesDir: 'uploadimages'
        );
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->uploadDir . '/*'));
        rmdir($this->uploadDir);
    }

    public function testUploadAssetReturnsJsonResponse(): void
    {
        $request = new Request();
        $file = $this->createMock(UploadedFile::class);
        $request->files->set('upload', $file);
        $this->uploadService
            ->expects(self::once())
            ->method('upload')
            ->with($file)
            ->willReturn(
                new UploadResult(
                    'stored.png',
                    'https://example.test/uploadfiles/stored.png',
                    'image/png',
                    123,
                    'png'
                )
            );

        $this->authentication
            ->method('authenticateByApiKey')
            ->willReturn($this->createMock(Administrator::class));

        $response = $this->controller->uploadAsset($request);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame(
            [
                'uploaded' => true,
                'file_name' => 'stored.png',
                'url' => 'https://example.test/uploadfiles/stored.png',
                'default' => 'https://example.test/uploadfiles/stored.png',
                'mime_type' => 'image/png',
                'size' => 123,
                'extension' => 'png',
            ],
            json_decode($response->getContent(), true)
        );
    }

    public function testUploadAssetRequiresAuthentication(): void
    {
        $request = new Request();
        $this->authentication->method('authenticateByApiKey')->willReturn(null);

        $this->expectException(AccessDeniedHttpException::class);
        $this->controller->uploadAsset($request);
    }

    public function testListFilesRequiresAuthentication(): void
    {
        $request = new Request();
        $this->authentication->method('authenticateByApiKey')->willReturn(null);

        $this->expectException(AccessDeniedHttpException::class);
        $this->controller->listFiles($request);
    }

    public function testListFilesWithDirectoryTraversal(): void
    {
        $request = new Request(['directory' => '../../../etc/passwd']);
        $this->authentication
            ->method('authenticateByApiKey')
            ->willReturn($this->createMock(Administrator::class));

        $this->uploadDirectoryValidator
            ->expects(self::once())
            ->method('validate')
            ->with('uploadimages/../../../etc/passwd')
            ->willThrowException(
                new BadRequestHttpException(
                    'Invalid directory name. Directory traversal is not allowed.'
                )
            );

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Directory traversal is not allowed');
        $this->controller->listFiles($request);
    }

    public function testListFilesWithAbsolutePath(): void
    {
        $request = new Request(['directory' => '/etc/passwd']);
        $this->authentication
            ->method('authenticateByApiKey')
            ->willReturn($this->createMock(Administrator::class));

        $this->uploadDirectoryValidator
            ->expects(self::once())
            ->method('validate')
            ->with('uploadimages/etc/passwd')
            ->willThrowException(
                new BadRequestHttpException(
                    'Invalid directory name. Directory traversal is not allowed.'
                )
            );

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Directory traversal is not allowed');
        $this->controller->listFiles($request);
    }

    public function testListFilesWithNonexistentDirectory(): void
    {
        $request = new Request(['directory' => 'nonexistent_dir_12345']);
        $this->authentication
            ->method('authenticateByApiKey')
            ->willReturn($this->createMock(Administrator::class));

        $this->uploadDirectoryValidator
            ->expects(self::once())
            ->method('validate')
            ->with('uploadimages/nonexistent_dir_12345')
            ->willThrowException(
                new NotFoundHttpException(
                    'Directory "nonexistent_dir_12345" not found.'
                )
            );

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Directory');
        $this->controller->listFiles($request);
    }

    public function testListFilesReturnsCorrectStructure(): void
    {
        $request = new Request(['directory' => 'uploads']);

        $this->authentication
            ->method('authenticateByApiKey')
            ->willReturn($this->createMock(Administrator::class));

        $this->uploadDirectoryValidator
            ->expects(self::once())
            ->method('validate')
            ->with('uploadimages/uploads')
            ->willReturn('/tmp/uploadimages/uploads');

        $files = [
            new DirectoryEntryDto(
                name: 'subdir',
                path: 'uploads/subdir',
                size: 0,
                type: 'directory',
                modified: 1234567890,
            ),
            new DirectoryEntryDto(
                name: 'test.png',
                path: 'uploads/test.png',
                size: 123,
                type: 'file',
                modified: 1234567891,
            ),
        ];

        $this->directoryListingService
            ->expects(self::once())
            ->method('list')
            ->with('/uploads', '/tmp/uploadimages/uploads')
            ->willReturn($files);

        $this->urlGenerator
            ->expects(self::once())
            ->method('generate')
            ->with(
                'editor_uploads_get_file',
                ['filename' => 'test.png'],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn('https://example.test/api/v2/editor-uploads/test.png');

        $response = $this->controller->listFiles($request);

        self::assertSame(200, $response->getStatusCode());

        self::assertSame(
            [
                'files' => [
                    [
                        'name' => 'subdir',
                        'url' => null,
                        'size' => 0,
                        'type' => 'directory',
                        'modified' => 1234567890,
                    ],
                    [
                        'name' => 'test.png',
                        'url' => 'https://example.test/api/v2/editor-uploads/test.png',
                        'size' => 123,
                        'type' => 'file',
                        'modified' => 1234567891,
                    ],
                ],
                'directory' => '/uploads',
                'total' => 2,
            ],
            json_decode($response->getContent(), true)
        );
    }

    public function testGetFileReturnsBinaryFileResponse(): void
    {
        $this->uploadDirectoryValidator
            ->expects(self::once())
            ->method('validate')
            ->with('uploadimages')
            ->willReturn($this->uploadDir);

        $response = $this->controller->getFile('test.png');

        self::assertInstanceOf(BinaryFileResponse::class, $response);
    }

    public function testGetFileWithNonexistentFileThrowsNotFoundException(): void
    {
        $this->uploadDirectoryValidator
            ->expects(self::once())
            ->method('validate')
            ->with('uploadimages')
            ->willReturn($this->uploadDir);

        $this->expectException(NotFoundHttpException::class);
        $this->controller->getFile('nonexistent.png');
    }

    public function testGetFileWithDirectoryTraversalThrowsNotFoundException(): void
    {
        $this->uploadDirectoryValidator
            ->expects(self::once())
            ->method('validate')
            ->with('uploadimages')
            ->willReturn($this->uploadDir);

        $this->expectException(NotFoundHttpException::class);
        $this->controller->getFile('../../etc/passwd');
    }
}
