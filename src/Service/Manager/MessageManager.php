<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Service\Manager;

use PhpList\Core\Domain\Model\Identity\Administrator;
use PhpList\Core\Domain\Model\Messaging\Message;
use PhpList\Core\Domain\Repository\Messaging\MessageRepository;
use PhpList\RestBundle\Entity\Dto\MessageContext;
use PhpList\RestBundle\Entity\Request\CreateMessageRequest;
use PhpList\RestBundle\Entity\Request\UpdateMessageRequest;
use PhpList\RestBundle\Service\Builder\MessageBuilder;

class MessageManager
{
    private MessageRepository $messageRepository;
    private MessageBuilder $messageBuilder;

    public function __construct(MessageRepository $messageRepository, MessageBuilder $messageBuilder)
    {
        $this->messageRepository = $messageRepository;
        $this->messageBuilder = $messageBuilder;
    }

    public function createMessage(CreateMessageRequest $createMessageRequest, Administrator $authUser): Message
    {
        $context = new MessageContext($authUser);
        $message = $this->messageBuilder->buildFromRequest($createMessageRequest, $context);
        $this->messageRepository->save($message);

        return $message;
    }

    public function updateMessage(
        UpdateMessageRequest $updateMessageRequest,
        Message $message,
        Administrator $authUser
    ): Message {
        $context = new MessageContext($authUser, $message);
        $message = $this->messageBuilder->buildFromRequest($updateMessageRequest, $context);
        $this->messageRepository->save($message);

        return $message;
    }
}
