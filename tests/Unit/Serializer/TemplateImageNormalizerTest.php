<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Serializer;

use PhpList\Core\Domain\Messaging\Model\Template;
use PhpList\Core\Domain\Messaging\Model\TemplateImage;
use PhpList\RestBundle\Serializer\TemplateImageNormalizer;
use PHPUnit\Framework\TestCase;

class TemplateImageNormalizerTest extends TestCase
{
    private TemplateImageNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new TemplateImageNormalizer();
    }

    public function testSupportsNormalizationOnlyForTemplateImage(): void
    {
        $this->assertTrue($this->normalizer->supportsNormalization($this->createMock(TemplateImage::class)));
        $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    }

    public function testNormalizeTemplateImage(): void
    {
        $template = $this->createMock(Template::class);
        $template->method('getId')->willReturn(42);

        $templateImage = $this->createMock(TemplateImage::class);
        $templateImage->method('getId')->willReturn(10);
        $templateImage->method('getTemplate')->willReturn($template);
        $templateImage->method('getMimeType')->willReturn('image/png');
        $templateImage->method('getFilename')->willReturn('test.png');
        $templateImage->method('getData')->willReturn('binary-data');
        $templateImage->method('getWidth')->willReturn(100);
        $templateImage->method('getHeight')->willReturn(200);

        $normalized = $this->normalizer->normalize($templateImage);

        $this->assertIsArray($normalized);
        $this->assertEquals([
            'id' => 10,
            'template_id' => 42,
            'mimetype' => 'image/png',
            'filename' => 'test.png',
            'data' => base64_encode('binary-data'),
            'width' => 100,
            'height' => 200,
        ], $normalized);
    }

    public function testNormalizeReturnsEmptyArrayForInvalidObject(): void
    {
        $normalized = $this->normalizer->normalize(new \stdClass());

        $this->assertIsArray($normalized);
        $this->assertEmpty($normalized);
    }
}
