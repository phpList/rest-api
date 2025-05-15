<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Request;

use PhpList\Core\Domain\Messaging\Model\Dto\CreateTemplateDto;
use PhpList\RestBundle\Messaging\Request\CreateTemplateRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CreateTemplateRequestTest extends TestCase
{
    private CreateTemplateRequest $request;
    
    protected function setUp(): void
    {
        $this->request = new CreateTemplateRequest();
    }
    
    public function testGetDtoReturnsCorrectDto(): void
    {
        $this->request->title = 'Test Template';
        $this->request->content = 'Test content with [CONTENT] placeholder';
        $this->request->text = 'Plain text with [TEXT] placeholder';
        $this->request->checkLinks = true;
        $this->request->checkImages = true;
        $this->request->checkExternalImages = true;
        
        $dto = $this->request->getDto();
        
        $this->assertInstanceOf(CreateTemplateDto::class, $dto);
        $this->assertEquals('Test Template', $dto->title);
        $this->assertEquals('Test content with [CONTENT] placeholder', $dto->content);
        $this->assertEquals('Plain text with [TEXT] placeholder', $dto->text);
        $this->assertNull($dto->fileContent);
        $this->assertTrue($dto->shouldCheckLinks);
        $this->assertTrue($dto->shouldCheckImages);
        $this->assertTrue($dto->shouldCheckExternalImages);
    }
    
    public function testGetDtoWithDefaultValues(): void
    {
        $this->request->title = 'Test Template';
        $this->request->content = 'Test content with [CONTENT] placeholder';

        $dto = $this->request->getDto();
        
        $this->assertInstanceOf(CreateTemplateDto::class, $dto);
        $this->assertEquals('Test Template', $dto->title);
        $this->assertEquals('Test content with [CONTENT] placeholder', $dto->content);
        $this->assertNull($dto->text);
        $this->assertNull($dto->fileContent);
        $this->assertFalse($dto->shouldCheckLinks);
        $this->assertFalse($dto->shouldCheckImages);
        $this->assertFalse($dto->shouldCheckExternalImages);
    }
    
    public function testGetDtoWithUploadedFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'Test file content');
        
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('getPathname')->willReturn($tempFile);
        
        $this->request->title = 'Test Template';
        $this->request->content = 'Test content with [CONTENT] placeholder';
        $this->request->file = $uploadedFile;
        
        $dto = $this->request->getDto();
        
        $this->assertInstanceOf(CreateTemplateDto::class, $dto);
        $this->assertEquals('Test Template', $dto->title);
        $this->assertEquals('Test content with [CONTENT] placeholder', $dto->content);
        $this->assertEquals('Test file content', $dto->fileContent);
        
        unlink($tempFile);
    }
}
