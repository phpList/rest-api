<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request;

use PhpList\RestBundle\Entity\Request\Message\MessageContentRequest;
use PhpList\RestBundle\Entity\Request\Message\MessageFormatRequest;
use PhpList\RestBundle\Entity\Request\Message\MessageOptionsRequest;
use PhpList\RestBundle\Entity\Request\Message\MessageScheduleRequest;
use Symfony\Component\Validator\Constraints as Assert;
use PhpList\RestBundle\Validator as CustomAssert;

class UpdateMessageRequest extends CreateMessageRequest
{
    public int $messageId;

    #[Assert\Valid]
    #[Assert\NotNull]
    public MessageContentRequest $content;

    #[Assert\Valid]
    #[Assert\NotNull]
    public MessageFormatRequest $format;

    #[Assert\Valid]
    #[Assert\NotNull]
    public MessageScheduleRequest $schedule;

    #[Assert\Valid]
    #[Assert\NotNull]
    public MessageOptionsRequest $options;

    #[CustomAssert\TemplateExists]
    public ?int $templateId;
}
