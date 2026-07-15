<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Messaging\Controller;

use PhpList\RestBundle\Messaging\Controller\EditorUploadController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;

class EditorUploadControllerTest extends AbstractTestController
{
    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(EditorUploadController::class, self::getContainer()->get(EditorUploadController::class));
    }

    public function testListFilesWithoutSessionKeyReturnsUnauthorized(): void
    {
        self::getClient()->request('GET', '/api/v2/editor-uploads');
        $this->assertHttpUnauthorized();
    }

    public function testListFilesWithValidSessionKeyReturnsOkay(): void
    {
        $this->authenticatedJsonRequest('GET', '/api/v2/editor-uploads');
        $this->assertHttpOkay();
    }

    public function testListFilesWithInvalidDirectoryTraversalReturnsError(): void
    {
        $this->authenticatedJsonRequest('GET', '/api/v2/editor-uploads', ['directory' => '../../../etc/passwd']);
        $this->assertHttpBadRequest();
    }

    public function testListFilesReturnsCorrectStructure(): void
    {
        $this->authenticatedJsonRequest('GET', '/api/v2/editor-uploads', ['directory' => '']);
        $this->assertHttpOkay();

        $response = $this->getDecodedJsonResponseContent();
        self::assertIsArray($response);
        self::assertArrayHasKey('files', $response);
        self::assertArrayHasKey('directory', $response);
        self::assertArrayHasKey('total', $response);
        self::assertIsArray($response['files']);
        self::assertIsInt($response['total']);
        self::assertEquals('uploads', $response['directory']);

        // Check file structure
        foreach ($response['files'] as $file) {
            self::assertArrayHasKey('name', $file);
            self::assertArrayHasKey('path', $file);
            self::assertArrayHasKey('size', $file);
            self::assertArrayHasKey('type', $file);
            self::assertArrayHasKey('modified', $file);
            self::assertIsString($file['name']);
            self::assertIsString($file['path']);
            self::assertIsInt($file['size']);
            self::assertIsString($file['type']);
            self::assertTrue(in_array($file['type'], ['file', 'directory']));
        }
    }
}
