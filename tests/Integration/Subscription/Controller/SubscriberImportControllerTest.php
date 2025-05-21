<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Subscription\Controller;

use PhpList\RestBundle\Subscription\Controller\SubscriberImportController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Integration tests for the SubscriberImportController.
 */
class SubscriberImportControllerTest extends AbstractTestController
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir();
    }

    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(
            SubscriberImportController::class,
            self::getContainer()->get(SubscriberImportController::class)
        );
    }

    public function testImportSubscribersWithoutSessionKeyReturnsForbiddenStatus(): void
    {
        self::getClient()->request('POST', '/api/v2/subscribers/import');

        $this->assertHttpForbidden();
    }

    public function testImportSubscribersWithoutFileReturnsBadRequestStatus(): void
    {
        $this->authenticatedJsonRequest('POST', '/api/v2/subscribers/import');

        $this->assertHttpBadRequest();
        $responseContent = $this->getDecodedJsonResponseContent();
        self::assertSame(false, $responseContent['success']);
        self::assertStringContainsString('No file uploaded', $responseContent['message']);
    }

    public function testImportSubscribersWithNonCsvFileReturnsBadRequestStatus(): void
    {
        $filePath = $this->tempDir . '/test.txt';
        file_put_contents($filePath, 'This is not a CSV file');
        
        $file = new UploadedFile(
            $filePath,
            'test.txt',
            'text/plain',
            null,
            true
        );

        $this->authenticatedJsonRequest(
            'POST',
            '/api/v2/subscribers/import',
            [],
            ['file' => $file]
        );

        $this->assertHttpBadRequest();
        $responseContent = $this->getDecodedJsonResponseContent();
        self::assertSame(false, $responseContent['success']);
        self::assertStringContainsString('File must be a CSV', $responseContent['message']);
    }

    public function testImportSubscribersWithValidCsvFile(): void
    {
        $filePath = $this->tempDir . '/subscribers.csv';
        $csvContent = "email,name\ntest@example.com,Test User\ntest2@example.com,Test User 2";
        file_put_contents($filePath, $csvContent);
        
        $file = new UploadedFile(
            $filePath,
            'subscribers.csv',
            'text/csv',
            null,
            true
        );

        $this->authenticatedJsonRequest(
            'POST',
            '/api/v2/subscribers/import',
            [],
            ['file' => $file]
        );

        $response = self::getClient()->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        
        $responseContent = $this->getDecodedJsonResponseContent();
        self::assertSame(true, $responseContent['success']);
        self::assertArrayHasKey('imported', $responseContent);
        self::assertArrayHasKey('skipped', $responseContent);
        self::assertArrayHasKey('errors', $responseContent);
    }

    public function testImportSubscribersWithOptions(): void
    {
        $filePath = $this->tempDir . '/subscribers.csv';
        $csvContent = "email,name\ntest@example.com,Test User";
        file_put_contents($filePath, $csvContent);
        
        $file = new UploadedFile(
            $filePath,
            'subscribers.csv',
            'text/csv',
            null,
            true
        );

        $this->authenticatedJsonRequest(
            'POST',
            '/api/v2/subscribers/import',
            [
                'request_confirmation' => 'true',
                'html_email' => 'false'
            ],
            ['file' => $file]
        );

        $response = self::getClient()->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        
        $responseContent = $this->getDecodedJsonResponseContent();
        self::assertSame(true, $responseContent['success']);
    }

    public function testGetMethodIsNotAllowed(): void
    {
        $this->authenticatedJsonRequest('GET', '/api/v2/subscribers/import');

        $this->assertHttpMethodNotAllowed();
    }
}
