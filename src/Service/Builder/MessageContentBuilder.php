<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Service\Builder;

use InvalidArgumentException;
use PhpList\Core\Domain\Model\Messaging\Message\MessageContent;
use PhpList\RestBundle\Entity\Request\Message\MessageContentRequest;

class MessageContentBuilder implements BuilderFromDtoInterface
{
    public function buildFromDto(object $dto, object $context = null): MessageContent
    {
        if (!$dto instanceof MessageContentRequest) {
            throw new InvalidArgumentException('Invalid request dto type: ' . get_class($dto));
        }

        return new MessageContent(
            $dto->subject,
            $dto->text,
            $dto->textMessage,
            $dto->footer
        );
    }
}
