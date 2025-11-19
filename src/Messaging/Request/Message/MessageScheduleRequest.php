<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Request\Message;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Model\Dto\Message\MessageScheduleDto;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'MessageScheduleRequest',
    required: ['embargo'],
    properties: [
        new OA\Property(property: 'embargo', type: 'string', format: 'date-time', example: '2025-04-17 09:00:00'),
        new OA\Property(property: 'repeat_interval', type: 'string', example: '24 hours'),
        new OA\Property(
            property: 'repeat_until',
            type: 'string',
            format: 'date-time',
            example: '2025-04-30T00:00:00+04:00'
        ),
        new OA\Property(property: 'requeue_interval', type: 'string', example: '12 hours'),
        new OA\Property(
            property: 'requeue_until',
            type: 'string',
            format: 'date-time',
            example: '2025-04-20T00:00:00+04:00'
        ),
    ],
    type: 'object'
)]
class MessageScheduleRequest implements RequestDtoInterface
{
    public ?int $repeatInterval = null;

    #[Assert\DateTime]
    public ?string $repeatUntil = null;

    public ?int $requeueInterval = null;

    #[Assert\DateTime]
    public ?string $requeueUntil = null;

    #[Assert\NotBlank]
    public string $embargo;

    public function getDto(): MessageScheduleDto
    {
        return new MessageScheduleDto(
            embargo: $this->embargo,
            repeatInterval: $this->repeatInterval,
            repeatUntil: $this->repeatUntil,
            requeueInterval: $this->requeueInterval,
            requeueUntil: $this->requeueUntil,
        );
    }
}
