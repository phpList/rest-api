<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Service\Manager;

use DateTime;
use PhpList\Core\Domain\Model\Identity\Administrator;
use PhpList\Core\Domain\Model\Messaging\Message;
use PhpList\Core\Domain\Repository\Messaging\MessageRepository;
use PhpList\Core\Domain\Repository\Messaging\TemplateRepository;
use PhpList\RestBundle\Entity\Request\CreateMessageRequest;

class MessageManager
{
    private MessageRepository $messageRepository;
    private TemplateRepository $templateRepository;

    public function __construct(MessageRepository $messageRepository, TemplateRepository $templateRepository)
    {
        $this->messageRepository = $messageRepository;
        $this->templateRepository = $templateRepository;
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
            new DateTime($createMessageRequest->schedule->embargo),
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
            $createMessageRequest->options->userSelection,
            null,
            null
        );

        if ($createMessageRequest->templateId > 0) {
            $template = $this->templateRepository->find($createMessageRequest->templateId);
        }

        $message = new Message($format, $schedule, $metadata, $content, $options, $authUser, $template ?? null);

        $this->messageRepository->save($message);

        return $message;
    }
}
