<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Request\Message;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Model\Dto\Message\MessageOptionsDto;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[OA\Schema(
    schema: 'MessageOptionsRequest',
    required: ['from_field'],
    properties: [
        new OA\Property(property: 'from_field', type: 'string', example: 'Jane Doe jane@example.com'),
        new OA\Property(property: 'to_field', type: 'string', example: 'subscriber@example.com'),
        new OA\Property(property: 'reply_to', type: 'string', example: 'reply@example.com'),
        new OA\Property(property: 'user_selection', type: 'string', example: 'all-active-users'),
    ],
    type: 'object'
)]
class MessageOptionsRequest implements RequestDtoInterface
{
    #[Assert\NotBlank(groups: [MessageMetadataRequest::NON_DRAFT_GROUP])]
    public string $fromField;

    #[Assert\Email]
    public string $toField;

    #[Assert\Email]
    public ?string $replyTo = null;

    public ?string $userSelection = null;

    /**
     * The from field may be a plain email or a name followed by an email, with or without angle brackets.
     */
    #[Assert\Callback]
    public function validateFromField(ExecutionContextInterface $context): void
    {
        if (!isset($this->fromField) || trim($this->fromField) === '') {
            return;
        }

        preg_match('/<([^<>]+)>\s*$|(\S+)\s*$/', $this->fromField, $matches);
        $email = $matches[1] !== '' ? $matches[1] : $matches[2];

        $context->getValidator()
            ->inContext($context)
            ->atPath('fromField')
            ->validate($email, new Assert\Email());
    }

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
