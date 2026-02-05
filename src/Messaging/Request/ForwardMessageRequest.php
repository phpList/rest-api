<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Request;

use OpenApi\Attributes as OA;
use PhpList\RestBundle\Common\Request\RequestInterface;
use PhpList\RestBundle\Messaging\Validator\Constraint\MaxForwardCount;
use PhpList\RestBundle\Messaging\Validator\Constraint\MaxPersonalNoteSize;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'ForwardMessageRequest',
    required: ['recipients'],
    properties: [
        new OA\Property(
            property: 'recipients',
            type: 'array',
            items: new OA\Items(type: 'string', format: 'email'),
            example: ['friend1@example.com', 'friend2@example.com']
        ),
        new OA\Property(
            property: 'uid',
            type: 'string',
            example: 'fwd-123e4567-e89b-12d3-a456-426614174000',
            nullable: true
        ),
        new OA\Property(
            property: 'note',
            type: 'string',
            example: 'Thought you might like this.',
            nullable: true
        ),
        new OA\Property(
            property: 'from_name',
            type: 'string',
            example: 'Alice',
            nullable: true
        ),
        new OA\Property(
            property: 'from_email',
            type: 'string',
            format: 'email',
            example: 'alice@example.com',
            nullable: true
        ),
    ],
    type: 'object'
)]
class ForwardMessageRequest implements RequestInterface
{
    /**
     * @var string[]
     */
    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Assert\Type('array')]
    #[Assert\Count(min: 1)]
    #[MaxForwardCount]
    #[Assert\All([
        'constraints' => [
            new Assert\Email([]),
            new Assert\Length(max: 255),
        ],
    ])]
    public array $recipients = [];

    #[Assert\Length(max: 255)]
    public ?string $uid = null;

    #[MaxPersonalNoteSize]
    public ?string $note = null;

    #[Assert\Length(max: 255)]
    public ?string $fromName = null;

    #[Assert\Email]
    #[Assert\Length(max: 255)]
    public ?string $fromEmail = null;

    public function getDto(): array
    {
        return [
            'recipients' => $this->recipients,
            'uid' => $this->uid,
            'note' => $this->note,
            'fromName' => $this->fromName,
            'fromEmail' => $this->fromEmail,
        ];
    }
}
