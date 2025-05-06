<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request;

use PhpList\Core\Domain\Model\Messaging\Dto\MessageDtoInterface;
use PhpList\Core\Domain\Model\Messaging\Dto\UpdateMessageDto;

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
