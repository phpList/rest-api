<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request;

use PhpList\RestBundle\Validator\Constraint as CustomAssert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

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
