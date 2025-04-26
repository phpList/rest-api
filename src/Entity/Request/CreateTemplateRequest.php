<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use PhpList\RestBundle\Validator as CustomAssert;

class CreateTemplateRequest
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
}
