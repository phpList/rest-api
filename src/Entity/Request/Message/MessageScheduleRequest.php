<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request\Message;

use Symfony\Component\Validator\Constraints as Assert;

class MessageScheduleRequest
{
    #[Assert\NotBlank]
    public int $repeatInterval;

    #[Assert\DateTime]
    public string $repeatUntil;

    #[Assert\NotBlank]
    public int $requeueInterval;

    #[Assert\DateTime]
    public string $requeueUntil;

    #[Assert\NotBlank]
    public string $embargo;
}
