<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request\Message;

use Symfony\Component\Validator\Constraints as Assert;

class MessageOptionsRequest
{
    #[Assert\Email]
    public string $fromField;

    #[Assert\Email]
    public string $toField;

    #[Assert\Email]
    public ?string $replyTo = null;

    #[Assert\NotBlank]
    public string $embargo;

    public ?string $userSelection = null;
}
