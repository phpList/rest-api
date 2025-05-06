<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request\Message;

use PhpList\Core\Domain\Model\Messaging\Dto\Message\MessageFormatDto;
use Symfony\Component\Validator\Constraints as Assert;

class MessageFormatRequest implements RequestDtoInterface
{
    #[Assert\Type('bool')]
    public bool $htmlFormated;

    #[Assert\Choice(['html', 'text', 'invite'])]
    public string $sendFormat;

    #[Assert\All([
        new Assert\Type('string'),
        new Assert\Choice(['text', 'html', 'pdf']),
    ])]
    public array $formatOptions;

    public function getDto(): MessageFormatDto
    {
        return new MessageFormatDto(
            htmlFormated: $this->htmlFormated,
            sendFormat: $this->sendFormat,
            formatOptions: $this->formatOptions,
        );
    }
}
