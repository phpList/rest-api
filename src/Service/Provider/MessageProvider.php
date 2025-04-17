<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Service\Provider;

use PhpList\Core\Domain\Model\Identity\Administrator;
use PhpList\Core\Domain\Model\Messaging\Message;
use PhpList\Core\Domain\Repository\Messaging\MessageRepository;

class MessageProvider
{
    private MessageRepository $messageRepository;

    public function __construct(
        MessageRepository $messageRepository,
    ) {
        $this->messageRepository = $messageRepository;
    }

    /** @return Message[] */
    public function getMessagesByOwner(Administrator $owner): array
    {
        return $this->messageRepository->getByOwnerId($owner->getId());
    }
}
