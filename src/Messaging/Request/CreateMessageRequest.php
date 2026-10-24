<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Request;

use PhpList\Core\Domain\Messaging\Model\Dto\CreateMessageDto;
use PhpList\Core\Domain\Messaging\Model\Dto\MessageDtoInterface;
use PhpList\RestBundle\Common\Request\RequestInterface;
use PhpList\RestBundle\Messaging\Request\Message\MessageContentRequest;
use PhpList\RestBundle\Messaging\Request\Message\MessageFormatRequest;
use PhpList\RestBundle\Messaging\Request\Message\MessageMetadataRequest;
use PhpList\RestBundle\Messaging\Request\Message\MessageOptionsRequest;
use PhpList\RestBundle\Messaging\Request\Message\MessageScheduleRequest;
use PhpList\RestBundle\Messaging\Validator\Constraint\TemplateExists;
use PhpList\Core\Domain\Messaging\Model\Message\MessageStatus;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CreateMessageRequest implements RequestInterface
{
    #[Assert\Valid]
    #[Assert\NotNull]
    public MessageContentRequest $content;

    #[Assert\Valid]
    #[Assert\NotNull]
    public MessageFormatRequest $format;

    #[Assert\Valid]
    #[Assert\NotNull]
    public MessageMetadataRequest $metadata;

    #[Assert\Valid]
    #[Assert\NotNull]
    public MessageScheduleRequest $schedule;

    #[Assert\Valid]
    #[Assert\NotNull]
    public MessageOptionsRequest $options;

    #[TemplateExists]
    public ?int $templateId = null;

    /**
     * Fields that must not be blank are only enforced when the message is not a draft.
     */
    #[Assert\Callback]
    public function validateNonDraft(ExecutionContextInterface $context): void
    {
        if (!isset($this->metadata->status) || $this->metadata->status === MessageStatus::Draft->value) {
            return;
        }

        $validator = $context->getValidator()->inContext($context);
        $groups = [MessageMetadataRequest::NON_DRAFT_GROUP];
        foreach (['content', 'schedule', 'options'] as $property) {
            if (isset($this->$property)) {
                $validator->atPath($property)->validate($this->$property, null, $groups);
            }
        }
    }

    public function getDto(): MessageDtoInterface
    {
        return new CreateMessageDto(
            content: $this->content->getDto(),
            format: $this->format->getDto(),
            metadata: $this->metadata->getDto(),
            options: $this->options->getDto(),
            schedule: $this->schedule->getDto(),
            templateId: $this->templateId,
        );
    }
}
