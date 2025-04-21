<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Service\Builder;

use InvalidArgumentException;
use PhpList\Core\Domain\Model\Messaging\Message\MessageFormat;
use PhpList\RestBundle\Entity\Request\Message\MessageFormatRequest;

class MessageFormatBuilder implements BuilderFromDtoInterface
{
    public function buildFromDto(object $dto, object $context = null): MessageFormat
    {
        if (!$dto instanceof MessageFormatRequest) {
            throw new InvalidArgumentException('Invalid request dto type: ' . get_class($dto));
        }

        return new MessageFormat(
            $dto->htmlFormated,
            $dto->sendFormat,
            $dto->formatOptions
        );
    }
}
