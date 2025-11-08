<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Request\Message;

use PhpList\Core\Domain\Messaging\Model\Dto\Message\MessageMetadataDto;
use PhpList\Core\Domain\Messaging\Model\Message\MessageStatus;
use Symfony\Component\Validator\Constraints as Assert;

class MessageMetadataRequest implements RequestDtoInterface
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
