<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Request;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Model\Dto\CreateTemplateDto;
use PhpList\RestBundle\Common\Request\RequestInterface;
use PhpList\RestBundle\Messaging\Validator\Constraint\ContainsPlaceholder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'CreateTemplateRequest',
    required: ['title'],
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Newsletter Template'),
        new OA\Property(property: 'content', type: 'string', example: '<html><body>[CONTENT]</body></html>'),
        new OA\Property(property: 'text', type: 'string', example: '[CONTENT]'),
        new OA\Property(
            property: 'file',
            description: 'Optional file upload for HTML content',
            type: 'string',
            format: 'binary'
        ),
        new OA\Property(
            property: 'check_links',
            description: 'Check that all links have full URLs',
            type: 'boolean',
            example: true
        ),
        new OA\Property(
            property: 'check_images',
            description: 'Check that all images have full URLs',
            type: 'boolean',
            example: false
        ),
        new OA\Property(
            property: 'check_external_images',
            description: 'Check that all external images exist',
            type: 'boolean',
            example: true
        ),
    ],
    type: 'object'
)]
class CreateTemplateRequest implements RequestInterface
{
    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\NotNull]
    public string $title;

    #[ContainsPlaceholder]
    public string $content;

    #[ContainsPlaceholder]
    public ?string $text = null;

    public ?UploadedFile $file = null;
    public bool $checkLinks = false;
    public bool $checkImages = false;
    public bool $checkExternalImages = false;

    public function getDto(): CreateTemplateDto
    {
        return new CreateTemplateDto(
            title: $this->title,
            content: $this->content,
            text: $this->text,
            fileContent: $this->file instanceof UploadedFile ? file_get_contents($this->file->getPathname()) : null,
            shouldCheckLinks: $this->checkLinks,
            shouldCheckImages: $this->checkImages,
            shouldCheckExternalImages: $this->checkExternalImages,
        );
    }
}
