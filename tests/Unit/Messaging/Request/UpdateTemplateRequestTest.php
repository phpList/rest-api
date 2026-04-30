<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Request;

use PhpList\Core\Domain\Messaging\Model\Dto\UpdateTemplateDto;
use PhpList\RestBundle\Messaging\Request\UpdateTemplateRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UpdateTemplateRequestTest extends TestCase
{
    private UpdateTemplateRequest $request;

    protected function setUp(): void
    {
        $this->request = new UpdateTemplateRequest();
    }

    public function testGetDtoReturnsCorrectDto(): void
    {
        $this->request->title = 'Updated Template';
        $this->request->content = 'Updated [CONTENT]';
        $this->request->text = 'Updated text';
        $this->request->listOrder = 7;
        $this->request->checkLinks = true;
        $this->request->checkImages = true;
        $this->request->checkExternalImages = true;

        $dto = $this->request->getDto();

        $this->assertInstanceOf(UpdateTemplateDto::class, $dto);
        $this->assertSame('Updated Template', $dto->title);
        $this->assertSame('Updated [CONTENT]', $dto->content);
        $this->assertSame('Updated text', $dto->text);
        $this->assertNull($dto->fileContent);
        $this->assertTrue($dto->shouldCheckLinks);
        $this->assertTrue($dto->shouldCheckImages);
        $this->assertTrue($dto->shouldCheckExternalImages);
        $this->assertSame(7, $this->request->listOrder);
    }

    public function testGetDtoWithUploadedFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'Updated file content');

        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('getPathname')->willReturn($tempFile);

        $this->request->title = 'Updated Template';
        $this->request->content = 'Updated [CONTENT]';
        $this->request->file = $uploadedFile;

        $dto = $this->request->getDto();

        $this->assertInstanceOf(UpdateTemplateDto::class, $dto);
        $this->assertSame('Updated file content', $dto->fileContent);
        $this->assertNull($this->request->listOrder);

        unlink($tempFile);
    }
}
