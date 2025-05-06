<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request\Message;

use PhpList\Core\Domain\Model\Messaging\Dto\Message\MessageScheduleDto;
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

    public function getDto(): MessageScheduleDto
    {
        return new MessageScheduleDto(
            embargo: $this->embargo,
            repeatInterval: $this->repeatInterval,
            repeatUntil: $this->repeatUntil,
            requeueInterval: $this->requeueInterval,
            requeueUntil: $this->requeueUntil,
        );
    }
}
