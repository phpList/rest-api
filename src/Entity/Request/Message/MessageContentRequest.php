<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request\Message;

use PhpList\Core\Domain\Model\Messaging\Dto\Message\MessageContentDto;
use Symfony\Component\Validator\Constraints as Assert;

class MessageContentRequest implements RequestDtoInterface
{
    #[Assert\NotBlank]
    public string $subject;

    #[Assert\NotBlank]
    public string $text;

    #[Assert\NotBlank]
    public string $textMessage;

    #[Assert\NotBlank]
    public string $footer;

    public function getDto(): MessageContentDto
    {
        return  new MessageContentDto(
            subject: $this->subject,
            text: $this->text,
            textMessage: $this->textMessage,
            footer: $this->footer,
        );
    }
}
