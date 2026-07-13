<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Controller;

use PhpList\Core\Domain\Identity\Model\Administrator;
use PhpList\Core\Security\Authentication;
use PhpList\Core\Domain\Common\Upload\UploadResult;
use PhpList\Core\Domain\Common\Upload\UploadService;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Messaging\Controller\EditorUploadController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EditorUploadControllerTest extends TestCase
{
    public function testUploadAssetReturnsJsonResponse(): void
    {
        $authentication = $this->createMock(Authentication::class);
        $validator = $this->createMock(RequestValidator::class);
        $uploadService = $this->createMock(UploadService::class);
        $request = new Request();
        $file = $this->createMock(UploadedFile::class);
        $request->files->set('upload', $file);

        $authentication->method('authenticateByApiKey')->willReturn($this->createMock(Administrator::class));
        $uploadService->expects(self::once())->method('upload')->with($file)->willReturn(
            new UploadResult('stored.png', 'https://example.test/uploadfiles/stored.png', 'image/png', 123, 'png')
        );

        $controller = new EditorUploadController($authentication, $validator, $uploadService);
        $response = $controller->uploadAsset($request);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame(
            [
                'uploaded' => true,
                'fileName' => 'stored.png',
                'url' => 'https://example.test/uploadfiles/stored.png',
                'default' => 'https://example.test/uploadfiles/stored.png',
                'mimeType' => 'image/png',
                'size' => 123,
                'extension' => 'png',
            ],
            json_decode($response->getContent(), true)
        );
    }

    public function testUploadAssetRequiresAuthentication(): void
    {
        $authentication = $this->createMock(Authentication::class);
        $validator = $this->createMock(RequestValidator::class);
        $uploadService = $this->createMock(UploadService::class);
        $request = new Request();

        $authentication->method('authenticateByApiKey')->willReturn(null);

        $controller = new EditorUploadController($authentication, $validator, $uploadService);

        $this->expectException(AccessDeniedHttpException::class);
        $controller->uploadAsset($request);
    }
}
