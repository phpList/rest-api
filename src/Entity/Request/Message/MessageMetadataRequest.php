<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request\Message;

use PhpList\Core\Domain\Model\Messaging\Dto\Message\MessageMetadataDto;
use Symfony\Component\Validator\Constraints as Assert;

class MessageMetadataRequest implements RequestDtoInterface
{
    #[Assert\NotBlank]
    public string $status;

    public function getDto(): MessageMetadataDto
    {
        return new MessageMetadataDto(
            status: $this->status,
        );
    }
}
