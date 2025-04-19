<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Service\Manager;

use DateTime;
use PhpList\Core\Domain\Model\Identity\Administrator;
use PhpList\Core\Domain\Model\Messaging\Message;
use PhpList\Core\Domain\Repository\Messaging\MessageRepository;
use PhpList\RestBundle\Entity\Request\CreateMessageRequest;

class MessageManager
{
    private MessageRepository $messageRepository;

    public function __construct(MessageRepository $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    public function createMessage(CreateMessageRequest $createMessageRequest, Administrator $authUser): Message
    {
        $format = new Message\MessageFormat(
            $createMessageRequest->format->htmlFormated,
            $createMessageRequest->format->sendFormat,
            $createMessageRequest->format->formatOptions,
        );

        $schedule = new Message\MessageSchedule(
            $createMessageRequest->schedule->repeatInterval,
            new DateTime($createMessageRequest->schedule->repeatUntil),
            $createMessageRequest->schedule->requeueInterval,
            new DateTime($createMessageRequest->schedule->requeueUntil),
        );

        $metadata = new Message\MessageMetadata($createMessageRequest->metadata->status);

        $content = new Message\MessageContent(
            $createMessageRequest->content->subject,
            $createMessageRequest->content->text,
            $createMessageRequest->content->textMessage,
            $createMessageRequest->content->footer,
        );

        $options = new Message\MessageOptions(
            $createMessageRequest->options->fromField ?? '',
            $createMessageRequest->options->toField ?? '',
            $createMessageRequest->options->replyTo ?? '',
            new DateTime($createMessageRequest->options->embargo),
            $createMessageRequest->options->userSelection,
            null,
            null
        );

        $message = new Message($format, $schedule, $metadata, $content, $options, $authUser);

        $this->messageRepository->save($message);

        return $message;
    }
}
