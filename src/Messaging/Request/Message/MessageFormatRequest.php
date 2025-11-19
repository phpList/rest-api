<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Request\Message;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Model\Dto\Message\MessageFormatDto;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'MessageFormatRequest',
    required: ['html_formated', 'send_format', 'format_options'],
    properties: [
        new OA\Property(property: 'html_formated', type: 'boolean', example: true),
        new OA\Property(
            property: 'send_format',
            type: 'string',
            enum: ['html', 'text', 'invite'],
            example: 'html'
        ),
        new OA\Property(
            property: 'format_options',
            type: 'array',
            items: new OA\Items(type: 'string', enum: ['text', 'html', 'pdf']),
            example: ['html']
        ),
    ],
    type: 'object'
)]
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
