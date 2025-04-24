<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class CreateTemplateRequest
{
    #[Assert\NotBlank]
    public string $title;

    #[Assert\NotBlank]
    public string $content;

    public ?string $text = null;

    public ?UploadedFile $file = null;
    public bool $checkLinks = false;
    public bool $checkImages = false;
    public bool $checkExternalImages = false;
}
