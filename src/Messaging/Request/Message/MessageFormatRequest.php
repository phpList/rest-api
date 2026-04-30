<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Request\Message;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Model\Dto\Message\MessageFormatDto;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'MessageFormatRequest',
    required: ['send_format'],
    properties: [
        new OA\Property(
            property: 'send_format',
            type: 'string',
            enum: ['html', 'text', 'invite'],
            example: 'html'
        ),
    ],
    type: 'object'
)]
class MessageFormatRequest implements RequestDtoInterface
{
    #[Assert\Choice(['html', 'text', 'invite'])]
    public string $sendFormat;

    public function getDto(): MessageFormatDto
    {
        return new MessageFormatDto(
            sendFormat: $this->sendFormat,
        );
    }
}
