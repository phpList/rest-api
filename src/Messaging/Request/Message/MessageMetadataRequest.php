<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Request\Message;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Model\Dto\Message\MessageMetadataDto;
use PhpList\Core\Domain\Messaging\Model\Message\MessageStatus;
use PhpList\RestBundle\Common\Request\RequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'MessageMetadataRequest',
    required: ['status'],
    properties: [
        new OA\Property(property: 'status', type: 'string', example: 'draft'),
    ],
    type: 'object'
)]
class MessageMetadataRequest implements RequestDtoInterface, RequestInterface
{
    #[Assert\NotBlank]
    public string $status;

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function getDto(): MessageMetadataDto
    {
        return new MessageMetadataDto(
            status: MessageStatus::from($this->status),
        );
    }
}
