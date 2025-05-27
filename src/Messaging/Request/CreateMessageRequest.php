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
use Symfony\Component\Validator\Constraints as Assert;

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
    public ?int $templateId;

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
