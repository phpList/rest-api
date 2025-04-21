<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Service\Builder;

use InvalidArgumentException;
use PhpList\Core\Domain\Model\Messaging\Message\MessageOptions;
use PhpList\RestBundle\Entity\Request\Message\MessageOptionsRequest;

class MessageOptionsBuilder implements BuilderFromDtoInterface
{
    public function buildFromDto(object $dto, object $context = null): MessageOptions
    {
        if (!$dto instanceof MessageOptionsRequest) {
            throw new InvalidArgumentException('Invalid request dto type: ' . get_class($dto));
        }

        return new MessageOptions(
            $dto->fromField ?? '',
            $dto->toField ?? '',
            $dto->replyTo ?? '',
            $dto->userSelection,
            null,
        );
    }
}
