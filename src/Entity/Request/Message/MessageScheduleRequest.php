<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request\Message;

use Symfony\Component\Validator\Constraints as Assert;

class MessageScheduleRequest implements RequestDtoInterface
{
    public ?int $repeatInterval = null;

    #[Assert\DateTime]
    public ?string $repeatUntil = null;

    public ?int $requeueInterval = null;

    #[Assert\DateTime]
    public ?string $requeueUntil = null;

    #[Assert\NotBlank]
    public string $embargo;
}
