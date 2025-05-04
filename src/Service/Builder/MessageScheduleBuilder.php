<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Service\Builder;

use DateTime;
use InvalidArgumentException;
use PhpList\Core\Domain\Model\Messaging\Message\MessageSchedule;
use PhpList\RestBundle\Entity\Request\Message\MessageScheduleRequest;

class MessageScheduleBuilder implements BuilderFromDtoInterface
{
    public function buildFromDto(object $dto, object $context = null): MessageSchedule
    {
        if (!$dto instanceof MessageScheduleRequest) {
            throw new InvalidArgumentException('Invalid request dto type: ' . get_class($dto));
        }

        return new MessageSchedule(
            $dto->repeatInterval,
            new DateTime($dto->repeatUntil),
            $dto->requeueInterval,
            new DateTime($dto->requeueUntil),
            new DateTime($dto->embargo)
        );
    }
}
