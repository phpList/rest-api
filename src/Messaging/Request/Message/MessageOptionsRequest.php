<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Request\Message;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Model\Dto\Message\MessageOptionsDto;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'MessageOptionsRequest',
    required: ['from_field'],
    properties: [
        new OA\Property(property: 'from_field', type: 'string', example: 'info@example.com'),
        new OA\Property(property: 'to_field', type: 'string', example: 'subscriber@example.com'),
        new OA\Property(property: 'reply_to', type: 'string', example: 'reply@example.com'),
        new OA\Property(property: 'user_selection', type: 'string', example: 'all-active-users'),
    ],
    type: 'object'
)]
class MessageOptionsRequest implements RequestDtoInterface
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $fromField;

    #[Assert\Email]
    public string $toField;

    #[Assert\Email]
    public ?string $replyTo = null;

    public ?string $userSelection = null;

    public function getDto(): MessageOptionsDto
    {
        return new MessageOptionsDto(
            fromField: $this->fromField,
            toField: $this->toField,
            replyTo: $this->replyTo,
            userSelection: $this->userSelection,
        );
    }
}
