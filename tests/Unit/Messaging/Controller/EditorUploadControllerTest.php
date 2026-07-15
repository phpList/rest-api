<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Controller;

use PhpList\Core\Domain\Common\Service\DirectoryListingService;
use PhpList\Core\Domain\Common\Validator\UploadDirectoryValidator;
use PhpList\Core\Domain\Identity\Model\Administrator;
use PhpList\Core\Security\Authentication;
use PhpList\Core\Domain\Common\Model\UploadResult;
use PhpList\Core\Domain\Common\Service\UploadService;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Messaging\Controller\EditorUploadController;
use PhpList\RestBundle\Messaging\Serializer\EditorUploadNormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EditorUploadControllerTest extends TestCase
{
    private EditorUploadController $controller;
    private Authentication|MockObject $authentication;
    private UploadService|MockObject $uploadService;
    private DirectoryListingService|MockObject $directoryListingService;
    private UploadDirectoryValidator|MockObject $uploadDirectoryValidator;

    protected function setUp(): void
    {
        $this->authentication = $this->createMock(Authentication::class);
        $validator = $this->createMock(RequestValidator::class);
        $this->uploadService = $this->createMock(UploadService::class);
        $this->directoryListingService = $this->createMock(DirectoryListingService::class);
        $this->uploadDirectoryValidator = $this->createMock(UploadDirectoryValidator::class);

        $this->controller = new EditorUploadController(
            authentication: $this->authentication,
            validator: $validator,
            uploadService: $this->uploadService,
            directoryListingService: $this->directoryListingService,
            uploadDirectoryValidator: $this->uploadDirectoryValidator,
            editorUploadNormalizer: new EditorUploadNormalizer()
        );
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
            ->with('uploads/../../../etc/passwd')
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
            ->with('uploads/etc/passwd')
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
            ->with('uploads/nonexistent_dir_12345')
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
            ->with('uploads/uploads')
            ->willReturn('/tmp/uploads/uploads');

        $files = [
            [
                'name' => 'subdir',
                'path' => '/uploads/uploads/subdir',
                'size' => 0,
                'type' => 'directory',
                'modified' => 1234567890,
            ],
            [
                'name' => 'test.png',
                'path' => '/uploads/uploads/test.png',
                'size' => 123,
                'type' => 'file',
                'modified' => 1234567891,
            ],
        ];

        $this->directoryListingService
            ->expects(self::once())
            ->method('list')
            ->with('uploads/uploads', '/tmp/uploads/uploads')
            ->willReturn($files);

        $response = $this->controller->listFiles($request);

        self::assertSame(200, $response->getStatusCode());

        self::assertSame(
            [
                'files' => $files,
                'directory' => 'uploads/uploads',
                'total' => 2,
            ],
            json_decode($response->getContent(), true)
        );
    }
}
