<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Messaging\Controller;

use PhpList\Core\Domain\Messaging\Model\Attachment;
use PhpList\RestBundle\Messaging\Controller\AttachmentController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Messaging\Fixtures\AttachmentFixture;

class AttachmentControllerTest extends AbstractTestController
{
    private string $repoPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repoPath = (string) self::getContainer()->getParameter('phplist.attachment_repository_path');
        if (!is_dir($this->repoPath)) {
            mkdir($this->repoPath, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up any test file we might have created
        $file = $this->repoPath . DIRECTORY_SEPARATOR . AttachmentFixture::FILENAME;
        if (is_file($file)) {
            unlink($file);
        }

        parent::tearDown();
    }

    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(
            AttachmentController::class,
            self::getContainer()->get(AttachmentController::class)
        );
    }

    public function testDownloadReturnsFileStreamWithHeaders(): void
    {
        $this->loadFixtures([AttachmentFixture::class]);

        // Prepare the actual file in the repository path
        $content = 'Hello Attachment';
        $file = $this->repoPath . DIRECTORY_SEPARATOR . AttachmentFixture::FILENAME;
        file_put_contents($file, $content);

        self::getClient()->request(
            'GET',
            sprintf(
                '/api/v2/attachments/download/%d?uid=%s',
                AttachmentFixture::ATTACHMENT_ID,
                Attachment::FORWARD
            )
        );

        $response = self::getClient()->getResponse();

        // StreamedResponse should be 200 with correct headers
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
        self::assertStringContainsString(
            'attachment; filename=' . AttachmentFixture::FILENAME,
            (string) $response->headers->get('Content-Disposition')
        );
        self::assertSame((string) strlen($content), $response->headers->get('Content-Length'));

        $callback = $response->getCallback();
        ob_start();
        $callback();
        $body = ob_get_clean();

        self::assertSame($content, $body);
    }

    public function testDownloadReturnsNotFoundWhenAttachmentEntityMissing(): void
    {
        self::getClient()->request('GET', '/api/v2/attachments/download/999999?uid=' . Attachment::FORWARD);
        $this->assertHttpNotFound();
    }

    public function testDownloadReturnsNotFoundWhenUidEmailNotFound(): void
    {
        $this->loadFixtures([AttachmentFixture::class]);

        // Do not create the file; the uid validation happens first and should 404
        self::getClient()->request(
            'GET',
            sprintf(
                '/api/v2/attachments/download/%d?uid=%s',
                AttachmentFixture::ATTACHMENT_ID,
                'does-not-exist@example.com'
            )
        );

        $this->assertHttpNotFound();
    }

    public function testDownloadReturnsNotFoundWhenFileMissing(): void
    {
        $this->loadFixtures([AttachmentFixture::class]);

        // Ensure no file exists
        $file = $this->repoPath . DIRECTORY_SEPARATOR . AttachmentFixture::FILENAME;
        if (is_file($file)) {
            unlink($file);
        }

        self::getClient()->request(
            'GET',
            sprintf(
                '/api/v2/attachments/download/%d?uid=%s',
                AttachmentFixture::ATTACHMENT_ID,
                Attachment::FORWARD
            )
        );

        $this->assertHttpNotFound();
    }
}
