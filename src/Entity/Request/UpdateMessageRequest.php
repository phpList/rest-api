<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request;

use PhpList\Core\Domain\Messaging\Model\Dto\MessageDtoInterface;
use PhpList\Core\Domain\Messaging\Model\Dto\UpdateMessageDto;

class UpdateMessageRequest extends CreateMessageRequest
{
    public int $messageId;

    public function getDto(): MessageDtoInterface
    {
        return new UpdateMessageDto(
            messageId: $this->messageId,
            content: $this->content->getDto(),
            format: $this->format->getDto(),
            metadata: $this->metadata->getDto(),
            options: $this->options->getDto(),
            schedule: $this->schedule->getDto(),
            templateId: $this->templateId,
        );
    }
}
