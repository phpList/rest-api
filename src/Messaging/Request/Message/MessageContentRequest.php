<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Request\Message;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Model\Dto\Message\MessageContentDto;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'MessageContentRequest',
    required: ['subject', 'text', 'footer'],
    properties: [
        new OA\Property(property: 'subject', type: 'string', example: 'Campaign Subject'),
        new OA\Property(property: 'text', type: 'string', example: 'Full text content'),
        new OA\Property(property: 'footer', type: 'string', example: 'Unsubscribe link here'),
    ],
    type: 'object'
)]
class MessageContentRequest implements RequestDtoInterface
{
    #[Assert\NotBlank]
    public string $subject;

    #[Assert\NotBlank]
    public string $text;

    #[Assert\NotBlank]
    public string $footer;

    public function getDto(): MessageContentDto
    {
        return  new MessageContentDto(
            subject: $this->subject,
            text: $this->text,
            footer: $this->footer,
        );
    }
}
