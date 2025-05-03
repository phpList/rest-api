<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Service\Builder;

use InvalidArgumentException;
use PhpList\Core\Domain\Model\Messaging\Message;
use PhpList\Core\Domain\Repository\Messaging\TemplateRepository;
use PhpList\RestBundle\Entity\Dto\MessageContext;
use PhpList\RestBundle\Entity\Request\CreateMessageRequest;
use PhpList\RestBundle\Entity\Request\RequestInterface;

class MessageBuilder implements BuilderFromRequestInterface
{
    public function __construct(
        private readonly TemplateRepository $templateRepository,
        private readonly MessageFormatBuilder $messageFormatBuilder,
        private readonly MessageScheduleBuilder $messageScheduleBuilder,
        private readonly MessageContentBuilder $messageContentBuilder,
        private readonly MessageOptionsBuilder $messageOptionsBuilder,
    ) {
    }

    public function buildFromRequest(RequestInterface $request, object $context = null): Message
    {
        if (!$request instanceof CreateMessageRequest) {
            throw new InvalidArgumentException('Invalid request type');
        }
        if (!$context instanceof MessageContext) {
            throw new InvalidArgumentException('Invalid context type');
        }

        $format = $this->messageFormatBuilder->buildFromDto($request->format);
        $schedule = $this->messageScheduleBuilder->buildFromDto($request->schedule);
        $content = $this->messageContentBuilder->buildFromDto($request->content);
        $options = $this->messageOptionsBuilder->buildFromDto($request->options);
        $template = null;
        if (isset($request->templateId)) {
            $template = $this->templateRepository->find($request->templateId);
        }

        if ($context->getExisting()) {
            $context->getExisting()->setFormat($format);
            $context->getExisting()->setSchedule($schedule);
            $context->getExisting()->setContent($content);
            $context->getExisting()->setOptions($options);
            $context->getExisting()->setTemplate($template);
            return $context->getExisting();
        }

        $metadata = new Message\MessageMetadata($request->metadata->status);

        return new Message($format, $schedule, $metadata, $content, $options, $context->getOwner(), $template);
    }
}
