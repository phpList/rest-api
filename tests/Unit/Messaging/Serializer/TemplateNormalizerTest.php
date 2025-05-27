<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Serializer;

use Doctrine\Common\Collections\ArrayCollection;
use PhpList\Core\Domain\Messaging\Model\Template;
use PhpList\Core\Domain\Messaging\Model\TemplateImage;
use PhpList\RestBundle\Messaging\Serializer\TemplateImageNormalizer;
use PhpList\RestBundle\Messaging\Serializer\TemplateNormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TemplateNormalizerTest extends TestCase
{
    private TemplateImageNormalizer&MockObject $templateImageNormalizer;
    private TemplateNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->templateImageNormalizer = $this->createMock(TemplateImageNormalizer::class);
        $this->normalizer = new TemplateNormalizer($this->templateImageNormalizer);
    }

    public function testSupportsNormalizationOnlyForTemplate(): void
    {
        $this->assertTrue($this->normalizer->supportsNormalization($this->createMock(Template::class)));
        $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    }

    public function testNormalizeTemplateWithImages(): void
    {
        $template = $this->createMock(Template::class);
        $template->method('getId')->willReturn(1);
        $template->method('getTitle')->willReturn('Test Template');
        $template->method('getContent')->willReturn('<html>Content</html>');
        $template->method('getText')->willReturn('Plain text');
        $template->method('getListOrder')->willReturn(5);

        $image = $this->createMock(TemplateImage::class);

        $template->method('getImages')->willReturn(new ArrayCollection([$image]));

        $this->templateImageNormalizer->expects($this->once())
            ->method('normalize')
            ->with($image)
            ->willReturn([
                'id' => 100,
                'filename' => 'test.png'
            ]);

        $normalized = $this->normalizer->normalize($template);

        $this->assertIsArray($normalized);
        $this->assertEquals([
            'id' => 1,
            'title' => 'Test Template',
            'content' => '<html>Content</html>',
            'text' => 'Plain text',
            'order' => 5,
            'images' => [
                [
                    'id' => 100,
                    'filename' => 'test.png'
                ]
            ]
        ], $normalized);
    }

    public function testNormalizeTemplateWithoutImages(): void
    {
        $template = $this->createMock(Template::class);
        $template->method('getId')->willReturn(2);
        $template->method('getTitle')->willReturn('Empty Template');
        $template->method('getContent')->willReturn('<html>No Images</html>');
        $template->method('getText')->willReturn('No images text');
        $template->method('getListOrder')->willReturn(0);

        $template->method('getImages')->willReturn(new ArrayCollection([]));

        $normalized = $this->normalizer->normalize($template);

        $this->assertIsArray($normalized);
        $this->assertEquals([
            'id' => 2,
            'title' => 'Empty Template',
            'content' => '<html>No Images</html>',
            'text' => 'No images text',
            'order' => 0,
            'images' => null
        ], $normalized);
    }

    public function testNormalizeReturnsEmptyArrayForInvalidObject(): void
    {
        $normalized = $this->normalizer->normalize(new \stdClass());

        $this->assertIsArray($normalized);
        $this->assertEmpty($normalized);
    }
}
