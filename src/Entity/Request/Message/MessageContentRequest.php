<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request\Message;

use Symfony\Component\Validator\Constraints as Assert;

class MessageContentRequest
{
    #[Assert\NotBlank]
    public string $subject;

    #[Assert\NotBlank]
    public string $text;

    #[Assert\NotBlank]
    public string $textMessage;

    #[Assert\NotBlank]
    public string $footer;
}
