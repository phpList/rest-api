<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request;

use PhpList\Core\Domain\Model\Messaging\Dto\CreateTemplateDto;
use PhpList\RestBundle\Validator\Constraint as CustomAssert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class CreateTemplateRequest implements RequestInterface
{
    #[Assert\NotBlank]
    #[Assert\NotNull]
    public string $title;

    #[Assert\NotBlank]
    #[CustomAssert\ContainsPlaceholder]
    public string $content;

    #[CustomAssert\ContainsPlaceholder]
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
